<template>
  <div class="col-md-3 col-6">
    <div :class="`small-box bg-${color}`">
      <div class="inner">
        <h3>{{ value }}</h3>
        <p>{{ title }}</p>
      </div>
      <div class="icon"><i :class="icon"></i></div>
    </div>
  </div>
</template>

<script setup>
defineProps({ icon: String, title: String, value: Number, color: String });
</script>

<style scoped>
.small-box {
  color: white;
  padding: 15px;
  border-radius: 0.25rem;
  position: relative;
  overflow: hidden;
}
.small-box .icon {
  position: absolute;
  top: -10px;
  right: 10px;
  font-size: 45px;
  opacity: 0.3;
}
</style>
