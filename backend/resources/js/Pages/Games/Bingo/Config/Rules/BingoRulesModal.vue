<script setup lang="ts">

</script>
<template>
  <Modal v-model="showModalForm" :title="modalTitle" size="lg" static>
  @if ($imageBingoRules)
                    <div class="d-flex justify-content-center">
                        <img style="max-height: 85px" src="{{ asset("storage/game/bingo/$imageBingoRules") }}"
                            alt="Game Image">
                    </div>
                    <hr>
                @endif
                <div>{!! $bingoRules !!}</div>
   </Modal>
</template>
