<template>
  <div>
    <pre>{{ project.boreholes }}</pre>
  </div>
</template>
<script>
export default {
  props: {
    project: {
      type: Object,
      required: true,
    },
  },
}
</script>
