<script setup>
// Inline SVG line icons, drawn for DriveDeal (no icon packages). OWNER: Lynn.
// Usage: <AppIcon name="search" />  or  <AppIcon name="heart" :size="18" label="Liked" />
// Icons inherit the text colour (stroke="currentColor"). Without a label they are hidden from
// screen readers (decorative); give a label only when the icon is used on its own.
import { computed } from 'vue'

const props = defineProps({
  name: { type: String, required: true },
  size: { type: [Number, String], default: 20 },
  label: { type: String, default: '' },
  strokeWidth: { type: [Number, String], default: 1.75 }
})

// Each shape: a path "d" string, or { c: [cx, cy, r] } circle, or { r: [x, y, w, h, rx] } rect.
// Add { fill: true } to fill a shape with currentColor.
const WHEELS = [{ c: [6.5, 16.5, 1.9] }, { c: [17.5, 16.5, 1.9] }]

const ICONS = {
  search: [{ c: [11, 11, 6.5] }, 'M16 16l4.5 4.5'],
  heart: [
    'M12 20.25s-8.25-4.9-8.25-10.6A4.4 4.4 0 0 1 12 7.2a4.4 4.4 0 0 1 8.25 2.45c0 5.7-8.25 10.6-8.25 10.6z'
  ],
  'heart-filled': [
    {
      d: 'M12 20.25s-8.25-4.9-8.25-10.6A4.4 4.4 0 0 1 12 7.2a4.4 4.4 0 0 1 8.25 2.45c0 5.7-8.25 10.6-8.25 10.6z',
      fill: true
    }
  ],
  car: [
    'M5 17H3.5v-4.25l2-4.9A2 2 0 0 1 7.35 6.5h9.3a2 2 0 0 1 1.85 1.35l2 4.9V17H19',
    'M3.5 12.75h17',
    'M9 17h6',
    { c: [7, 17, 2] },
    { c: [17, 17, 2] }
  ],
  gauge: ['M4.3 17.5a8.75 8.75 0 1 1 15.4 0', 'M12 13.5l3.75-3.75', { c: [12, 13.5, 1.25] }],
  gear: [
    { c: [6, 5, 1.6] },
    { c: [12, 5, 1.6] },
    { c: [18, 5, 1.6] },
    { c: [6, 19, 1.6] },
    { c: [12, 19, 1.6] },
    'M6 6.6v10.8M12 6.6v10.8M18 6.6V12H6'
  ],
  fuel: [
    'M4.5 20.5V5a1.75 1.75 0 0 1 1.75-1.75h5.5A1.75 1.75 0 0 1 13.5 5v15.5',
    'M3 20.5h12',
    { r: [7, 6.5, 4, 3.75, 0.75] },
    'M13.5 9.5h1.75a1.75 1.75 0 0 1 1.75 1.75v4.5a1.5 1.5 0 0 0 3 0V8.25L17.25 5.5'
  ],
  pin: [
    'M12 21s-6.75-5.75-6.75-11.25a6.75 6.75 0 0 1 13.5 0C18.75 15.25 12 21 12 21z',
    { c: [12, 9.75, 2.4] }
  ],
  user: [{ c: [12, 8, 3.75] }, 'M4.75 20.25a7.25 7.25 0 0 1 14.5 0'],
  users: [
    { c: [9, 8.25, 3.4] },
    'M2.75 20a6.25 6.25 0 0 1 12.5 0',
    'M15.5 4.95a3.4 3.4 0 0 1 0 6.6',
    'M17.75 14.25A6.25 6.25 0 0 1 21.25 20'
  ],
  'shield-check': [
    'M12 3.25l7.25 2.9v5.35c0 4.4-3.05 8.05-7.25 9.25-4.2-1.2-7.25-4.85-7.25-9.25V6.15z',
    'M8.75 12.1l2.2 2.2 4.3-4.55'
  ],
  message: [
    'M20.25 15.25a1.75 1.75 0 0 1-1.75 1.75H8.25l-4.5 3.5V5.5a1.75 1.75 0 0 1 1.75-1.75h13a1.75 1.75 0 0 1 1.75 1.75z',
    'M8 9h8M8 12.5h5'
  ],
  tag: [
    'M3.25 11.9V4.25a1 1 0 0 1 1-1h7.65a1 1 0 0 1 .7.3l8.3 8.3a1 1 0 0 1 0 1.4l-7.65 7.65a1 1 0 0 1-1.4 0l-8.3-8.3a1 1 0 0 1-.3-.7z',
    { c: [7.75, 7.75, 1.5] }
  ],
  sparkles: [
    'M10.5 3.5l1.75 5 5 1.75-5 1.75-1.75 5-1.75-5-5-1.75 5-1.75z',
    'M18.25 14.25l.75 2 2 .75-2 .75-.75 2-.75-2-2-.75 2-.75z',
    'M18 3.5l.5 1.25 1.25.5-1.25.5L18 7l-.5-1.25-1.25-.5 1.25-.5z'
  ],
  'arrow-right': ['M4.5 12h15', 'M13.5 6l6 6-6 6'],
  'arrow-left': ['M19.5 12h-15', 'M10.5 6l-6 6 6 6'],
  'chevron-down': ['M6 9.25l6 6 6-6'],
  'chevron-left': ['M14.75 6l-6 6 6 6'],
  'chevron-right': ['M9.25 6l6 6-6 6'],
  plus: ['M12 5v14', 'M5 12h14'],
  upload: [
    'M12 15.25V4.25',
    'M7.5 8.5L12 4l4.5 4.5',
    'M4 15v3.75A1.25 1.25 0 0 0 5.25 20h13.5A1.25 1.25 0 0 0 20 18.75V15'
  ],
  trash: [
    'M4 7h16',
    'M9.25 7V4.5h5.5V7',
    'M6.25 7l.9 12.5a1.25 1.25 0 0 0 1.25 1.15h7.2a1.25 1.25 0 0 0 1.25-1.15L17.75 7',
    'M10 11v5.5M14 11v5.5'
  ],
  edit: ['M4 20h4L18.6 9.4a2.12 2.12 0 0 0-3-3L5 17v3', 'M13.75 8.25l3 3'],
  eye: [
    'M2.5 12S6 5.75 12 5.75 21.5 12 21.5 12 18 18.25 12 18.25 2.5 12 2.5 12z',
    { c: [12, 12, 3] }
  ],
  check: ['M5 12.5l4.5 4.5L19 7.5'],
  x: ['M6 6l12 12', 'M18 6L6 18'],
  menu: ['M4 7h16', 'M4 12h16', 'M4 17h16'],
  calendar: [{ r: [3.75, 5, 16.5, 15.25, 2] }, 'M3.75 10h16.5', 'M8 3v4M16 3v4'],
  palette: ['M12 3.5s6.25 6.6 6.25 10.75a6.25 6.25 0 0 1-12.5 0C5.75 10.1 12 3.5 12 3.5z'],
  sliders: ['M4 7h9.5M18.5 7H20M4 17h3.5M12.5 17H20', { c: [16, 7, 2.25] }, { c: [10, 17, 2.25] }],
  mail: [{ r: [3, 5, 18, 14, 2] }, 'M3.75 6.75l8.25 6.25 8.25-6.25'],
  phone: [
    'M5.25 3.75h3l1.5 4-2 1.5a11.5 11.5 0 0 0 7 7l1.5-2 4 1.5v3a1.75 1.75 0 0 1-1.75 1.75A16.5 16.5 0 0 1 3.5 5.5a1.75 1.75 0 0 1 1.75-1.75z'
  ],
  logout: [
    'M9.5 20.25H6a1.75 1.75 0 0 1-1.75-1.75v-13A1.75 1.75 0 0 1 6 3.75h3.5',
    'M15.5 8l4 4-4 4',
    'M19.5 12H9.5'
  ],
  image: [{ r: [3, 4, 18, 16, 2] }, { c: [8.5, 9.5, 1.6] }, 'M21 15.5l-5-5L6.5 20'],
  info: [{ c: [12, 12, 8.75] }, 'M12 11v5.25', 'M12 7.75h.01'],
  alert: [
    'M10.3 4.4L2.85 17.5A2 2 0 0 0 4.6 20.5h14.8a2 2 0 0 0 1.75-3L13.7 4.4a2 2 0 0 0-3.4 0z',
    'M12 9.5v4',
    'M12 17h.01'
  ],
  home: ['M3.75 11L12 4l8.25 7', 'M6 9.25v10.5h12V9.25', 'M10 19.75v-5h4v5'],
  clock: [{ c: [12, 12, 8.75] }, 'M12 7.5V12l3 2'],
  grid: [
    { r: [4, 4, 6.5, 6.5, 1.5] },
    { r: [13.5, 4, 6.5, 6.5, 1.5] },
    { r: [4, 13.5, 6.5, 6.5, 1.5] },
    { r: [13.5, 13.5, 6.5, 6.5, 1.5] }
  ],
  list: ['M9 6.5h11M9 12h11M9 17.5h11', 'M4.5 6.5h.01M4.5 12h.01M4.5 17.5h.01'],
  // Clipboard with a tick: "deal agreed"
  deal: [
    'M15.5 5h1.75A1.75 1.75 0 0 1 19 6.75v12.5A1.75 1.75 0 0 1 17.25 21H6.75A1.75 1.75 0 0 1 5 19.25V6.75A1.75 1.75 0 0 1 6.75 5H8.5',
    { r: [8.5, 3, 7, 4, 1.25] },
    'M8.75 13.5l2.25 2.25 4.25-4.5'
  ],
  // Body types, side view
  sedan: [
    'M4.6 16.5H3.25a.75.75 0 0 1-.75-.75v-2.1a1.25 1.25 0 0 1 1.05-1.25L7 11.75l2.55-2.85A2 2 0 0 1 11.05 8.25h3.7a2 2 0 0 1 1.5.7l2.5 2.8 2.15.4a1.25 1.25 0 0 1 1.1 1.25v2.35a.75.75 0 0 1-.75.75h-.85',
    'M8.4 16.5h7.2',
    ...WHEELS
  ],
  hatchback: [
    'M4.6 16.5H3.25a.75.75 0 0 1-.75-.75v-2.4a1.25 1.25 0 0 1 1-1.2L7 11.4l2.6-3a2 2 0 0 1 1.5-.65h6.5a2 2 0 0 1 1.9 1.4l1.2 3.6.6.3a1 1 0 0 1 .55.9v1.8a.75.75 0 0 1-.75.75h-1.15',
    'M8.4 16.5h7.2',
    ...WHEELS
  ],
  suv: [
    'M4.6 16.5H3.25a.75.75 0 0 1-.75-.75v-3.2a1 1 0 0 1 .8-1L6.5 10.9l1.9-3.65a1.5 1.5 0 0 1 1.35-.8h9.75a1.5 1.5 0 0 1 1.5 1.5v2.55l.5.45a1 1 0 0 1 .35.75v4.05a.75.75 0 0 1-.75.75h-1.1',
    'M8.4 16.5h7.2',
    'M9.75 6.5v4.4',
    ...WHEELS
  ],
  mpv: [
    'M4.6 16.5H3.25a.75.75 0 0 1-.75-.75v-2.7a1 1 0 0 1 .7-.95L6.25 11l3.05-4.3a1.75 1.75 0 0 1 1.45-.7h8.75a2 2 0 0 1 2 2v7.75a.75.75 0 0 1-.75.75h-1.15',
    'M8.4 16.5h7.2',
    'M14.5 6v5',
    ...WHEELS
  ],
  pickup: [
    'M4.6 16.5H3.25a.75.75 0 0 1-.75-.75v-2.6a1 1 0 0 1 .8-1l3.2-.65 2.1-3.35a1.5 1.5 0 0 1 1.25-.65H13v4.75h8.5v3.5a.75.75 0 0 1-.75.75h-1.15',
    'M8.4 16.5h7.2',
    ...WHEELS
  ]
}

const shapes = computed(() =>
  (ICONS[props.name] || ICONS.info).map((shape) =>
    typeof shape === 'string' ? { d: shape } : shape
  )
)
</script>

<template>
  <svg
    xmlns="http://www.w3.org/2000/svg"
    :width="size"
    :height="size"
    viewBox="0 0 24 24"
    fill="none"
    stroke="currentColor"
    :stroke-width="strokeWidth"
    stroke-linecap="round"
    stroke-linejoin="round"
    class="dd-icon"
    :role="label ? 'img' : undefined"
    :aria-label="label || undefined"
    :aria-hidden="label ? undefined : 'true'"
    focusable="false"
  >
    <template v-for="(shape, index) in shapes" :key="index">
      <circle
        v-if="shape.c"
        :cx="shape.c[0]"
        :cy="shape.c[1]"
        :r="shape.c[2]"
        :fill="shape.fill ? 'currentColor' : 'none'"
      />
      <rect
        v-else-if="shape.r"
        :x="shape.r[0]"
        :y="shape.r[1]"
        :width="shape.r[2]"
        :height="shape.r[3]"
        :rx="shape.r[4]"
        :fill="shape.fill ? 'currentColor' : 'none'"
      />
      <path v-else :d="shape.d" :fill="shape.fill ? 'currentColor' : 'none'" />
    </template>
  </svg>
</template>

<style scoped>
.dd-icon {
  flex-shrink: 0;
  vertical-align: middle;
}
</style>
