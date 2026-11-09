// One way for every page to call the PHP API. Shared file: don't change it without telling the group.
//
// Examples:
//   const data = await apiGet('listings.php', { make: 'Toyota', sort: 'price_asc', page: 1 })
//   const car  = await apiGet('listings.php', { id: 3 })
//   const res  = await apiPost('inquiries.php', { listing_id: 3, buyer_id: 2, message: 'Hi' })
//   await apiPut('listings.php', { listing_id: 3, price: 50000, seller_id: 2 })
//   await apiDelete('listings.php', { listing_id: 3, seller_id: 2 })
//   const form = new FormData(); form.append('image', file); form.append('listing_id', 3)
//   const { image_path } = await apiUpload('upload.php', form)
//   <img :src="imageUrl(car.image_path)">
//
// Every helper returns the parsed JSON. If PHP answers {"error": "..."} with a 4xx/5xx code,
// the helper throws an Error with that message, so pages can do:
//   try { ... } catch (e) { errorMessage.value = e.message }

// import.meta.env.BASE_URL is '/' in "npm run dev" (Vite proxies /api to PHP)
// and './' in the build (api/ sits next to index.html on Mercury).
export const BASE_URL = import.meta.env.BASE_URL + 'api/'

async function request(endpoint, { method = 'GET', params, body, formData } = {}) {
  let url = BASE_URL + endpoint

  if (params) {
    const query = new URLSearchParams()
    for (const [key, value] of Object.entries(params)) {
      if (value !== undefined && value !== null && value !== '') query.append(key, value)
    }
    if (query.toString()) url += '?' + query.toString()
  }

  const options = { method, headers: {} }

  // PUT and DELETE are sent as POST plus a header, because some servers (Mercury included)
  // can block real PUT/DELETE requests. api/helpers.php turns them back into PUT/DELETE,
  // so PHP code just checks request_method() === 'PUT' as normal.
  if (method === 'PUT' || method === 'DELETE') {
    options.method = 'POST'
    options.headers['X-HTTP-Method-Override'] = method
  }

  if (formData) {
    options.body = formData // the browser sets the multipart Content-Type itself
  } else if (body !== undefined) {
    options.headers['Content-Type'] = 'application/json'
    options.body = JSON.stringify(body)
  }

  const response = await fetch(url, options)
  const text = await response.text()

  let data
  try {
    data = text ? JSON.parse(text) : {}
  } catch {
    // PHP printed an error or warning page instead of JSON
    throw new Error(`${endpoint} did not return JSON: ${text.slice(0, 200)}`)
  }

  if (!response.ok) {
    throw new Error(data.error || `${endpoint} failed (${response.status})`)
  }
  return data
}

export const apiGet = (endpoint, params) => request(endpoint, { params })
export const apiPost = (endpoint, body) => request(endpoint, { method: 'POST', body })
export const apiPut = (endpoint, body) => request(endpoint, { method: 'PUT', body })
export const apiDelete = (endpoint, body) => request(endpoint, { method: 'DELETE', body })
export const apiUpload = (endpoint, formData) => request(endpoint, { method: 'POST', formData })

/** Turn "uploads/abc.jpg" from the database into a URL an <img> can load. */
export function imageUrl(path) {
  if (!path) return ''
  if (/^https?:\/\//.test(path)) return path // already a full URL
  return import.meta.env.BASE_URL + path
}
