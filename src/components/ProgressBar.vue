<template>
  <div class="flex items-center w-48 h-full">
    <div class="relative w-full h-2 bg-gray-200/10 rounded-full overflow-hidden">
      <div :style="{ width: value + '%' }" class="h-full rounded-full" :class="color"></div>
    </div>
  </div>
</template>

<script>
export default {
  name: "ProgressBar",
  props: {
    value: {
      type: Number,
      required: true,
    },
    label: {
      type: String,
      default: "Progress",
    },
    color: {
      type: String,
      default: "bg-blue-500",
    },
  },
  computed: {
    clampedValue() {
      return Math.max(0, Math.min(this.value, 100));
    },
  },
};
</script>

<style scoped>
/* Optionnel: Ajoutez un style personnalisé ici si nécessaire */
</style>