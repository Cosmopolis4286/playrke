<script setup lang="ts" name="DashboardQuickLinks">
  export interface QuickLink {
    label: string
    description?: string
    icon: string
    href: string
    variant?: 'primary' | 'success' | 'info' | 'warning' | 'secondary' | 'danger'
  }

  withDefaults(defineProps<{
    links: QuickLink[]
    title?: string
    columns?: 2 | 3 | 4
  }>(), {
    columns: 4,
  })
</script>

<template>
  <div v-if="links.length" class="ql">
    <p v-if="title" class="ql__title">{{ title }}</p>
    <div class="ql__grid" :class="`ql__grid--${columns}`">
      <a
        v-for="link in links"
        :key="link.href"
        :href="link.href"
        class="ql__item"
        :class="`ql__item--${link.variant ?? 'primary'}`"
      >
        <div class="ql__icon" :class="`ql__icon--${link.variant ?? 'primary'}`">
          <i :class="link.icon"></i>
        </div>
        <span class="ql__label">{{ link.label }}</span>
        <span v-if="link.description" class="ql__desc">{{ link.description }}</span>
      </a>
    </div>
  </div>
</template>

<style scoped>
.ql__title {
  font-size: 0.7rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: .05em;
  color: var(--ds-text-2);
  margin-bottom: 0.6rem;
}

.ql__grid {
  display: grid;
  gap: 0.6rem;
}
.ql__grid--2 { grid-template-columns: repeat(2, 1fr); }
.ql__grid--3 { grid-template-columns: repeat(3, 1fr); }
.ql__grid--4 { grid-template-columns: repeat(4, 1fr); }

@media (max-width: 575px) {
  .ql__grid--3,
  .ql__grid--4 { grid-template-columns: repeat(2, 1fr); }
}

/* Item */
.ql__item {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 0.35rem;
  padding: 0.85rem 0.5rem;
  border-radius: 10px;
  border: 1px solid var(--ds-border);
  background: var(--ds-surface);
  text-decoration: none;
  color: var(--ds-text);
  text-align: center;
  transition: box-shadow 0.16s, transform 0.16s;
}
.ql__item:hover {
  box-shadow: var(--ds-card-hover-shadow);
  transform: translateY(-2px);
  text-decoration: none;
  color: var(--ds-text);
}

/* Iconos */
.ql__icon {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1rem;
  flex-shrink: 0;
}
.ql__icon--primary   { background: rgba(13,110,253,.13); color: #0d6efd; }
.ql__icon--success   { background: rgba(25,135,84,.13);  color: #198754; }
.ql__icon--info      { background: rgba(13,202,240,.13); color: #0aa2c0; }
.ql__icon--warning   { background: rgba(255,193,7,.13);  color: #b08000; }
.ql__icon--secondary { background: var(--ds-surface-2);  color: var(--ds-text-2); }
.ql__icon--danger    { background: rgba(220,53,69,.13);  color: #dc3545; }

/* Texto */
.ql__label {
  font-size: 0.78rem;
  font-weight: 600;
  color: var(--ds-text);
  line-height: 1.2;
}
.ql__desc {
  font-size: 0.68rem;
  color: var(--ds-text-muted);
  line-height: 1.2;
}
</style>
