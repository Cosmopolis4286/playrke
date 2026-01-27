<script setup lang="ts" name="DirectoryButtons">
  const emit = defineEmits<{ (e: 'create'): void }>()
</script>

<template>
  <div class="mt-2">
    <button @click="$emit('create')" class="btn btn-sm btn-success" type="button">
      <i class="fas fa-plus-square"></i> Registrar
    </button>
  </div>
</template>
