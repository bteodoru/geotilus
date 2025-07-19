<template>
  <div>
    <div class="p-6">
      <button
        v-if="!isCurrentProject"
        type="button"
        @click="switchProject(project)"
        :disabled="loading"
        class="rounded-md bg-white px-2.5 py-1.5 text-sm font-semibold text-gray-900 shadow-xs ring-1 ring-gray-300 ring-inset hover:bg-gray-50 transition-colors duration-200 disabled:opacity-60 disabled:cursor-not-allowed"
      >
        <!-- <span v-if="loading">Switching...</span> -->
        <!-- <span v-else>Comută la</span> -->
        <span>Comută la</span>
      </button>
      <span v-else class="rounded-md bg-green-600 px-2.5 py-1.5 text-sm font-semibold text-white ring-1 ring-green-600 ring-inset"> Proiect current </span>
      <pre class="mt-4">{{ project }}</pre>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from "vue"
import { router, usePage } from "@inertiajs/vue3"

const props = defineProps({
  project: {
    type: Object,
    required: true,
  },
})

const loading = ref(false)

const page = usePage()

const currentProjectId = computed(() => {
  return page.props.auth.user?.current_project_id
})

const isCurrentProject = computed(() => {
  return props.project.id === currentProjectId.value
})

const switchProject = (project) => {
  if (loading.value || isCurrentProject.value) {
    return
  }

  loading.value = true

  router.put(
    route("current-project.update"),
    {
      current_project_id: project.id,
    },
    {
      preserveState: true,
      onSuccess: () => {
        loading.value = false
        // current_project_id se actualizează automat prin Inertia
        // și va declanșa re-render-ul componentei
      },
      onError: () => {
        loading.value = false
        console.error("Failed to switch project. Please try again.")
      },
    }
  )
}
</script>
