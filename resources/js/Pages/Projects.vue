<template>
  <div>
    <!-- <h3 class="text-lg font-semibold text-gray-900 mb-4">Select Current Project</h3>
    <ul class="space-y-2">
      <li
        v-for="project in projects"
        :key="project.id"
        class="flex items-center justify-between p-4 bg-white border border-gray-200 rounded-lg hover:shadow-md transition-shadow duration-200"
        :class="{ 'border-green-500 bg-green-50': project.id === currentProjectId }"
      >
        <div class="flex-1">
          <a :href="`/projects/${project.id}`" class="text-lg font-medium text-gray-900 hover:text-blue-600 transition-colors">
            {{ project.name }}
          </a>
          <span
            class="inline-block ml-2 px-2 py-1 text-xs font-medium rounded"
            :class="{
              'bg-green-100 text-green-800': project.status.toLowerCase() === 'active',
              'bg-gray-100 text-gray-800': project.status.toLowerCase() !== 'active',
            }"
          >
            {{ project.status }}
          </span>
          <p class="mt-2 text-sm text-gray-600" v-if="project.description">
            {{ truncateDescription(project.description) }}
          </p>
        </div>
        <div class="ml-4">
          <button
            @click="switchProject(project)"
            :disabled="loading || project.id === currentProjectId"
            class="px-4 py-2 text-sm font-medium border rounded-md transition-colors duration-200 disabled:opacity-60 disabled:cursor-not-allowed"
            :class="{
              'bg-green-600 text-white border-green-600': project.id === currentProjectId,
              'bg-white text-gray-700 border-gray-300 hover:bg-gray-50 hover:border-gray-400': project.id !== currentProjectId && !(loading && selectedProjectId === project.id),
              'opacity-70': loading && selectedProjectId === project.id,
            }"
          >
            <span v-if="project.id === currentProjectId">Current</span>
            <span v-else-if="loading && selectedProjectId === project.id">Switching...</span>
            <span v-else>Make Current</span>
          </button>
        </div>
      </li>
    </ul> -->
    <div class="px-4 sm:px-6 lg:px-8">
      <!-- <div class="sm:flex sm:items-center max-w-3xl mx-auto">
        <div class="sm:flex-auto">
          <h1 class="text-base font-semibold text-gray-900">Users</h1>
          <p class="mt-2 text-sm text-gray-700">A list of all the users in your account including their name, title, email and role.</p>
        </div>
        <div class="mt-4 sm:mt-0 sm:ml-16 sm:flex-none">
          <button type="button" class="block rounded-md bg-indigo-600 px-3 py-2 text-center text-sm font-semibold text-white shadow-xs hover:bg-indigo-500 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
            Add user
          </button>
        </div>
      </div> -->
      <ul role="list" class="divide-y divide-gray-100 mt-8">
        <li v-for="project in projects" :key="project.id" class="hover:bg-gray-50">
          <div class="flex items-center justify-between gap-x-6 py-5 max-w-3xl mx-auto">
            <div class="min-w-0">
              <div class="flex items-start gap-x-3">
                <Link :href="route('projects.show', project.id)" class="text-sm/6 font-semibold text-gray-900">
                  {{ project.name }}
                </Link>
                <!-- <p class="text-sm/6 font-semibold text-gray-900">{{ project.name }}</p> -->
                <p :class="[statuses[project.status], 'mt-0.5 rounded-md px-1.5 py-0.5 text-xs font-medium whitespace-nowrap ring-1 ring-inset']">{{ project.status }}</p>
              </div>
              <div class="mt-1 flex items-center gap-x-2 text-xs/5 text-gray-500">
                <!-- <p class="whitespace-nowrap">
                Due on <time :datetime="project.dueDateTime">{{ project.dueDate }}</time>
              </p> -->
                <!-- <svg viewBox="0 0 2 2" class="size-0.5 fill-current">
                <circle cx="1" cy="1" r="1" />
              </svg> -->
                <p class="truncate">{{ project.client.name }}</p>
              </div>
            </div>
            <div class="flex flex-none items-center gap-x-4">
              <!-- <span :class="[statuses[project.status], 'mt-0.5 rounded-md px-1.5 py-0.5 text-xs font-medium whitespace-nowrap ring-1 ring-inset']">{{ project.status }}</span> -->

              <!-- <a :href="project.name" class="hidden rounded-md bg-white px-2.5 py-1.5 text-sm font-semibold text-gray-900 shadow-xs ring-1 ring-gray-300 ring-inset hover:bg-gray-50 sm:block"
              >Make curent<span class="sr-only">, {{ project.name }}</span></a
            > -->
              <button
                v-if="project.id !== currentProjectId"
                type="button"
                :key="project.id"
                @click="switchProject(project)"
                :disabled="loading || project.id === currentProjectId"
                class="rounded-md bg-white px-2.5 py-1.5 text-sm font-semibold text-gray-900 shadow-xs ring-1 ring-gray-300 ring-inset hover:bg-gray-50 sm:block transition-colors duration-200 disabled:opacity-60 disabled:cursor-not-allowed"
                :class="{
                  'bg-green-600 text-white border-green-600': project.id === currentProjectId,
                  'bg-white text-gray-700 border-gray-300 hover:bg-gray-50 hover:border-gray-400': project.id !== currentProjectId && !(loading && selectedProjectId === project.id),
                  'opacity-70': loading && selectedProjectId === project.id,
                }"
              >
                <span v-if="project.id === currentProjectId">Current</span>
                <span v-else-if="loading && selectedProjectId === project.id">Switching...</span>
                <span v-else>Make Current</span>
              </button>
              <svg v-else xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="text-blue-500 size-6">
                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
              </svg>
            </div>
          </div>
        </li>
      </ul>
      <div class="mt-8 flow-root">
        <div class="-mx-4 -my-2 sm:-mx-6 lg:-mx-8">
          <div class="inline-block min-w-full py-2 align-middle">
            <table class="min-w-full border-separate border-spacing-0">
              <thead>
                <tr>
                  <th scope="col" class="sticky top-0 z-10 border-b border-gray-300 bg-white/75 py-3.5 pr-3 pl-4 text-left text-sm font-semibold text-gray-900 backdrop-blur-sm backdrop-filter sm:pl-6 lg:pl-8">Titlu</th>
                  <th scope="col" class="sticky top-0 z-10 hidden border-b border-gray-300 bg-white/75 px-3 py-3.5 text-left text-sm font-semibold text-gray-900 backdrop-blur-sm backdrop-filter sm:table-cell">Beneficiar</th>
                  <th scope="col" class="sticky top-0 z-10 hidden border-b border-gray-300 bg-white/75 px-3 py-3.5 text-left text-sm font-semibold text-gray-900 backdrop-blur-sm backdrop-filter lg:table-cell">Status</th>
                  <!-- <th scope="col" class="sticky top-0 z-10 border-b border-gray-300 bg-white/75 px-3 py-3.5 text-left text-sm font-semibold text-gray-900 backdrop-blur-sm backdrop-filter">Role</th> -->
                  <!-- <th scope="col" class="sticky top-0 z-10 border-b border-gray-300 bg-white/75 py-3.5 pr-4 pl-3 backdrop-blur-sm backdrop-filter sm:pr-6 lg:pr-8">
                    <span class="sr-only">Edit</span>
                  </th> -->
                </tr>
              </thead>
              <tbody>
                <tr v-for="(project, projectIdx) in projects" :key="project.id">
                  <td :class="[projectIdx !== projects.length - 1 ? 'border-b border-gray-200' : '', 'py-4 pr-3 pl-4 text-sm font-medium whitespace-nowrap text-gray-900 sm:pl-6 lg:pl-8']">{{ project.name }}</td>
                  <td :class="[projectIdx !== projects.length - 1 ? 'border-b border-gray-200' : '', 'hidden px-3 py-4 text-sm whitespace-nowrap text-gray-500 sm:table-cell']">{{ project.client.name }}</td>
                  <!-- <td :class="[projectIdx !== projects.length - 1 ? 'border-b border-gray-200' : '', 'hidden px-3 py-4 text-sm whitespace-nowrap text-gray-500 sm:table-cell']">{{ truncateDescription(project.description) }}</td> -->
                  <td :class="[projectIdx !== projects.length - 1 ? 'border-b border-gray-200' : '', 'hidden px-3 py-4 text-sm whitespace-nowrap text-gray-500 lg:table-cell']">
                    <span :class="[statuses[project.status], 'mt-0.5 rounded-md px-1.5 py-0.5 text-xs font-medium whitespace-nowrap ring-1 ring-inset']">{{ project.status }}</span>

                    <!-- {{ project.status }} -->
                  </td>
                  <!-- <td :class="[projectIdx !== projects.length - 1 ? 'border-b border-gray-200' : '', 'px-3 py-4 text-sm whitespace-nowrap text-gray-500']">{{ project.role }}</td> -->
                  <!-- <td :class="[projectIdx !== projects.length - 1 ? 'border-b border-gray-200' : '', 'relative py-4 pr-4 pl-3 text-right text-sm font-medium whitespace-nowrap sm:pr-8 lg:pr-8']">
                    <a href="#" class="text-indigo-600 hover:text-indigo-900"
                      >Edit<span class="sr-only">, {{ project.name }}</span></a
                    >
                  </td> -->
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { router, Link } from "@inertiajs/vue3"
const statuses = {
  Complete: "text-green-700 bg-green-50 ring-green-600/20",
  "In progress": "text-gray-600 bg-gray-50 ring-gray-500/10",
  Archived: "text-yellow-800 bg-yellow-50 ring-yellow-600/20",
}
export default {
  components: {
    Link,
  },
  props: {
    projects: {
      type: Array, // Corectez tipul din Object în Array
      required: true,
    },
    currentProjectId: {
      type: Number,
      default: null,
    },
  },
  data() {
    return {
      loading: false,
      selectedProjectId: null,
      statuses: {
        "On Hold": "text-green-700 bg-green-50 ring-green-600/20",
        Done: "text-gray-600 bg-gray-50 ring-gray-500/10",
        Active: "text-yellow-800 bg-yellow-50 ring-yellow-600/20",
      },
    }
  },
  mounted() {},
  methods: {
    switchProject(project) {
      if (this.loading || project.id === this.currentProjectId) {
        return
      }

      this.loading = true
      this.selectedProjectId = project.id

      router.put(
        route("current-project.update"),
        {
          project_id: project.id, // Corectez numele câmpului
        },
        {
          preserveState: true,
          onSuccess: () => {
            this.loading = false
            this.selectedProjectId = null
          },
          onError: () => {
            this.loading = false
            this.selectedProjectId = null
            // Poți adăuga aici gestionarea erorilor
            alert("Failed to switch project. Please try again.")
          },
        }
      )
    },
    truncateDescription(description) {
      if (!description) return ""
      return description.length > 64 ? description.substring(0, 64) + "..." : description
    },
  },
}
</script>
