<template>
  <div>
    <h2>Información del Juego</h2>
    <pre>{{ data }}</pre>
  </div>
</template>

<script>
export default {
  props: {
    data: {
      type: Object,
      required: true
    }
  }
}
</script>
