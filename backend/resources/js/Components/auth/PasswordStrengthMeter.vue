<script setup lang="ts">
  import { computed } from 'vue'
  import { PASSWORD_RULES } from '@js/composables/auth/passwordRules'

  const props = defineProps<{ password: string }>()

  const RULES = PASSWORD_RULES

  const passed = computed(() => RULES.map(rule => rule.test(props.password)))
  const score = computed(() => passed.value.filter(Boolean).length)

  const level = computed(() => {
    if (score.value <= 2) return 'weak'
    if (score.value <= 4) return 'medium'
    return 'strong'
  })

  const levelLabel = computed(() => ({
    weak: 'Débil',
    medium: 'Media',
    strong: 'Fuerte',
  }[level.value]))
</script>

<template>
  <div class="password-strength" :class="`password-strength--${level}`">
    <div class="password-strength__bar">
      <span
        v-for="i in RULES.length"
        :key="i"
        class="password-strength__segment"
        :class="{ 'password-strength__segment--filled': i <= score }"
      />
    </div>
    <p class="password-strength__label">{{ levelLabel }}</p>
    <ul class="password-strength__rules">
      <li
        v-for="(rule, i) in RULES"
        :key="rule.label"
        class="password-strength__rule"
        :class="{ 'password-strength__rule--ok': passed[i] }"
      >
        <i :class="passed[i] ? 'fa-solid fa-check' : 'fa-solid fa-xmark'" aria-hidden="true"></i>
        {{ rule.label }}
      </li>
    </ul>
  </div>
</template>

<style scoped>
.password-strength {
  margin: -2px 0 8px;
}

.password-strength__bar {
  display: flex;
  gap: 4px;
  margin-bottom: 4px;
}

.password-strength__segment {
  flex: 1;
  height: 3px;
  border-radius: 2px;
  background: rgba(255, 255, 255, 0.10);
  transition: background 0.2s;
}

.password-strength--weak .password-strength__segment--filled { background: #f87171; }
.password-strength--medium .password-strength__segment--filled { background: #fbbf24; }
.password-strength--strong .password-strength__segment--filled { background: var(--mint, #2af598); }

.password-strength__label {
  margin: 0 0 4px;
  font-size: 10.5px;
  font-weight: 600;
  letter-spacing: 0.5px;
  text-transform: uppercase;
}

.password-strength--weak .password-strength__label { color: #f87171; }
.password-strength--medium .password-strength__label { color: #fbbf24; }
.password-strength--strong .password-strength__label { color: var(--mint, #2af598); }

.password-strength__rules {
  list-style: none;
  margin: 0;
  padding: 0;
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 2px 8px;
}

.password-strength__rule {
  display: flex;
  align-items: center;
  gap: 4px;
  font-size: 10px;
  color: rgba(148, 163, 184, 0.65);
}

.password-strength__rule i {
  font-size: 9px;
  color: rgba(248, 113, 113, 0.7);
}

.password-strength__rule--ok {
  color: rgba(148, 163, 184, 0.95);
}

.password-strength__rule--ok i {
  color: var(--mint, #2af598);
}
</style>
