<script setup lang="ts">
interface ButterflyConfig {
  size: number
  top: string
  left: string
  duration: number
  delay: number
  color: string
  path: number
}

const butterflies: ButterflyConfig[] = [
  { size: 44, top: '12%', left: '6%', duration: 18, delay: 0, color: '#f48fb1', path: 0 },
  { size: 52, top: '22%', left: '80%', duration: 22, delay: 3, color: '#ce93d8', path: 1 },
  { size: 36, top: '58%', left: '10%', duration: 16, delay: 5, color: '#81d4fa', path: 0 },
  { size: 48, top: '78%', left: '75%', duration: 24, delay: 1, color: '#f48fb1', path: 1 },
  { size: 40, top: '44%', left: '52%', duration: 20, delay: 4, color: '#e1bee7', path: 2 },
  { size: 56, top: '90%', left: '42%', duration: 17, delay: 6, color: '#f8bbd0', path: 0 },
  { size: 32, top: '35%', left: '88%', duration: 15, delay: 7, color: '#b3e5fc', path: 1 },
  { size: 46, top: '5%', left: '50%', duration: 21, delay: 2, color: '#ce93d8', path: 2 },
  { size: 38, top: '18%', left: '35%', duration: 14, delay: 8, color: '#f48fb1', path: 0 },
  { size: 50, top: '50%', left: '20%', duration: 19, delay: 2.5, color: '#81d4fa', path: 1 },
  { size: 42, top: '65%', left: '55%', duration: 23, delay: 5.5, color: '#f48fb1', path: 2 },
  { size: 34, top: '8%', left: '70%', duration: 16, delay: 9, color: '#f8bbd0', path: 0 },
  { size: 54, top: '85%', left: '15%', duration: 20, delay: 1.5, color: '#ce93d8', path: 1 },
  { size: 30, top: '30%', left: '42%', duration: 13, delay: 6.5, color: '#e1bee7', path: 2 },
  { size: 48, top: '95%', left: '60%', duration: 18, delay: 4.5, color: '#81d4fa', path: 0 },
  { size: 40, top: '68%', left: '90%', duration: 22, delay: 0.5, color: '#f48fb1', path: 1 },
]
</script>

<template>
  <div class="fixed inset-0 pointer-events-none z-0 overflow-hidden" aria-hidden="true">
    <div
      v-for="(b, i) in butterflies"
      :key="i"
      class="butterfly-pos"
      :style="{
        width: b.size + 'px',
        height: b.size + 'px',
        top: b.top,
        left: b.left,
      }"
    >
      <div
        :class="['butterfly-fly', b.path === 1 ? 'fly-path-1' : b.path === 2 ? 'fly-path-2' : 'fly-path-0']"
        :style="{
          animationDuration: b.duration + 's',
          animationDelay: b.delay + 's',
        }"
      >
        <svg
          viewBox="0 0 100 100"
          class="butterfly-svg"
          :style="{
            color: b.color,
            animationDelay: '-' + (i * 1.5) + 's',
          }"
        >
          <path
            d="M50 50 C30 22 6 30 10 48 C14 62 32 64 50 50Z"
            fill="currentColor"
            opacity="0.85"
          />
          <path
            d="M50 50 C70 22 94 30 90 48 C86 62 68 64 50 50Z"
            fill="currentColor"
            opacity="0.85"
          />
          <path
            d="M50 50 C34 64 20 80 30 88 C38 94 46 74 50 50Z"
            fill="currentColor"
            opacity="0.65"
          />
          <path
            d="M50 50 C66 64 80 80 70 88 C62 94 54 74 50 50Z"
            fill="currentColor"
            opacity="0.65"
          />
          <ellipse cx="50" cy="52" rx="3" ry="14" fill="#5c2d6b" opacity="0.5" />
          <path d="M50 38 C48 28 40 20 34 17" fill="none" stroke="#5c2d6b" stroke-width="1.5" opacity="0.4" />
          <path d="M50 38 C52 28 60 20 66 17" fill="none" stroke="#5c2d6b" stroke-width="1.5" opacity="0.4" />
          <circle cx="34" cy="17" r="1.5" fill="#5c2d6b" opacity="0.4" />
          <circle cx="66" cy="17" r="1.5" fill="#5c2d6b" opacity="0.4" />
        </svg>
      </div>
    </div>
  </div>
</template>

<style scoped>
.butterfly-pos {
  position: absolute;
  will-change: transform;
}

.butterfly-fly {
  width: 100%;
  height: 100%;
}

.butterfly-fly.fly-path-0 {
  animation: fly-path-0 ease-in-out infinite;
}

.butterfly-fly.fly-path-1 {
  animation: fly-path-1 ease-in-out infinite;
}

.butterfly-fly.fly-path-2 {
  animation: fly-path-2 ease-in-out infinite;
}

.butterfly-svg {
  width: 100%;
  height: 100%;
  display: block;
  animation:
    flap 0.35s ease-in-out infinite,
    hue-shift 12s linear infinite;
  transform-origin: 50% 50%;
  filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.1));
}

@keyframes fly-path-0 {
  0% {
    transform: translate(0, 0) rotate(-3deg);
  }
  15% {
    transform: translate(70px, -50px) rotate(2deg);
  }
  30% {
    transform: translate(140px, -20px) rotate(4deg);
  }
  45% {
    transform: translate(100px, 40px) rotate(-2deg);
  }
  60% {
    transform: translate(40px, 70px) rotate(-5deg);
  }
  75% {
    transform: translate(-30px, 40px) rotate(1deg);
  }
  90% {
    transform: translate(-50px, 10px) rotate(3deg);
  }
  100% {
    transform: translate(0, 0) rotate(-3deg);
  }
}

@keyframes fly-path-1 {
  0% {
    transform: translate(0, 0) rotate(5deg);
  }
  20% {
    transform: translate(-60px, -40px) rotate(-2deg);
  }
  40% {
    transform: translate(-120px, 10px) rotate(-5deg);
  }
  60% {
    transform: translate(-70px, 60px) rotate(2deg);
  }
  80% {
    transform: translate(-20px, 30px) rotate(4deg);
  }
  100% {
    transform: translate(0, 0) rotate(5deg);
  }
}

@keyframes fly-path-2 {
  0% {
    transform: translate(0, 0) rotate(0deg);
  }
  25% {
    transform: translate(50px, -60px) rotate(6deg);
  }
  50% {
    transform: translate(-40px, -100px) rotate(-3deg);
  }
  75% {
    transform: translate(-90px, -30px) rotate(4deg);
  }
  100% {
    transform: translate(0, 0) rotate(0deg);
  }
}

@keyframes flap {
  0%, 100% {
    transform: scaleX(1) scaleY(1);
  }
  25% {
    transform: scaleX(0.25) scaleY(0.92);
  }
  50% {
    transform: scaleX(0.1) scaleY(0.85);
  }
  75% {
    transform: scaleX(0.35) scaleY(0.92);
  }
}

@keyframes hue-shift {
  0% {
    filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.1)) hue-rotate(0deg);
  }
  25% {
    filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.1)) hue-rotate(90deg);
  }
  50% {
    filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.1)) hue-rotate(180deg);
  }
  75% {
    filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.1)) hue-rotate(270deg);
  }
  100% {
    filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.1)) hue-rotate(360deg);
  }
}
</style>
