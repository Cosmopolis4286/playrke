<template>
  <BingoForm :timeZone="timeZone" :automaticRoom="automaticRoom" :creator="creator" />
</template>

<script setup>
import BingoForm from "@/components/games/bingo/views/form/BingoForm.vue";
</script>

<style scoped>
/* Opcional: estilos específicos para el componente */
</style>