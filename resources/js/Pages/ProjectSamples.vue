<template>
  <div>
    <pre>{{ samples }}</pre>
  </div>
</template>
<script>
export default {
  props: {
    samples: {
      type: Object,
      required: true,
    },
  },
}
</script>
