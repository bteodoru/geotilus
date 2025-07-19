<template>
  <div>
    <pre>{{ sample }}</pre>
    <p>{{ sample.granulometry.clay }}, {{ sample.granulometry.silt }}, {{ sample.granulometry.sand }}</p>
    <p>{{ sample.soil_type }}</p>
  </div>
</template>
<script>
export default {
  props: {
    sample: {
      type: Object,
      required: true,
    },
  },
}
</script>
