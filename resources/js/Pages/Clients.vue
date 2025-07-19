<template>
  <div>
    <pre>{{ clients }}</pre>
  </div>
</template>
<script>
export default {
  props: {
    clients: {
      type: Object,
      required: true,
    },
  },
}
</script>
