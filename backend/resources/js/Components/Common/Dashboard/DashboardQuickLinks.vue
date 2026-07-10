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
  color: #6c757d;
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
  border: 1px solid #e9ecef;
  background: #fff;
  text-decoration: none;
  color: inherit;
  text-align: center;
  transition: box-shadow 0.16s, transform 0.16s;
}
.ql__item:hover {
  box-shadow: 0 4px 14px rgba(0,0,0,.08);
  transform: translateY(-2px);
  text-decoration: none;
  color: inherit;
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
.ql__icon--primary   { background: #e7f0ff; color: #0d6efd; }
.ql__icon--success   { background: #e6f4ed; color: #198754; }
.ql__icon--info      { background: #e0f8ff; color: #0aa2c0; }
.ql__icon--warning   { background: #fff8e1; color: #b08000; }
.ql__icon--secondary { background: #ebebeb; color: #495057; }
.ql__icon--danger    { background: #fce8ea; color: #dc3545; }

/* Texto */
.ql__label {
  font-size: 0.78rem;
  font-weight: 600;
  color: #212529;
  line-height: 1.2;
}
.ql__desc {
  font-size: 0.68rem;
  color: #8a9bb0;
  line-height: 1.2;
}
</style>
