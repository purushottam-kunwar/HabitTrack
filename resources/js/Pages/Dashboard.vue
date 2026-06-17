<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';

const today = new Date().toISOString().split('T')[0];
const selectedDate = ref(today);
const logs = ref([]);
const loading = ref(false);

const confirmTarget = ref(null); // { id, name } of the log pending removal
const removing = ref(false);

const totalSpent = computed(() => logs.value.reduce((sum, l) => sum + parseFloat(l.amount_spent || 0), 0).toFixed(2));
const totalCalories = computed(() => logs.value.reduce((sum, l) => sum + ((l.food_item?.calories || 0) * l.quantity), 0));

async function fetchLogs() {
    loading.value = true;
    const res = await axios.get('/api/habit-logs', { params: { date: selectedDate.value } });
    logs.value = res.data;
    loading.value = false;
}

function askRemove(log) {
    confirmTarget.value = { id: log.id, name: log.food_item?.name };
}

function cancelRemove() {
    confirmTarget.value = null;
}

async function confirmRemove() {
    if (!confirmTarget.value) return;
    removing.value = true;
    try {
        await axios.delete(`/api/habit-logs/${confirmTarget.value.id}`);
        logs.value = logs.value.filter(l => l.id !== confirmTarget.value.id);
    } finally {
        confirmTarget.value = null;
        removing.value = false;
    }
}

onMounted(fetchLogs);
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Daily Food Log</h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-6">

                <!-- Date Picker -->
                <div class="bg-white shadow-sm rounded-lg p-6 flex flex-wrap items-center gap-4">
                    <label class="font-medium text-gray-700">Select Date:</label>
                    <input
                        type="date"
                        v-model="selectedDate"
                        @change="fetchLogs"
                        :max="today"
                        class="border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500"
                    />
                    <Link
                        :href="route('log')"
                        class="ml-auto bg-green-600 text-white px-4 py-2 rounded-md text-sm hover:bg-green-700 transition"
                    >
                        + Log Food / Drink
                    </Link>
                </div>

                <!-- Summary Cards -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="bg-white rounded-lg shadow-sm p-5 text-center border-l-4 border-blue-500">
                        <p class="text-3xl font-bold text-blue-600">{{ logs.length }}</p>
                        <p class="text-sm text-gray-500 mt-1">Items Logged</p>
                    </div>
                    <div class="bg-white rounded-lg shadow-sm p-5 text-center border-l-4 border-orange-400">
                        <p class="text-3xl font-bold text-orange-500">{{ totalCalories }}</p>
                        <p class="text-sm text-gray-500 mt-1">Est. Calories</p>
                    </div>
                    <div class="bg-white rounded-lg shadow-sm p-5 text-center border-l-4 border-yellow-500">
                        <p class="text-3xl font-bold text-yellow-600">₹{{ totalSpent }}</p>
                        <p class="text-sm text-gray-500 mt-1">Spent Today</p>
                    </div>
                </div>

                <!-- Log List -->
                <div class="bg-white shadow-sm rounded-lg p-6">
                    <h3 class="font-semibold text-gray-700 mb-4">
                        Food & Drink Log for <span class="text-green-600">{{ selectedDate }}</span>
                    </h3>

                    <div v-if="loading" class="text-center text-gray-400 py-8">Loading...</div>

                    <div v-else-if="logs.length === 0" class="text-center py-12">
                        <p class="text-gray-400 text-lg">No entries for this date.</p>
                        <Link :href="route('log')" class="mt-3 inline-block text-green-600 hover:underline text-sm">
                            + Add your first entry
                        </Link>
                    </div>

                    <ul v-else class="divide-y divide-gray-100">
                        <li v-for="log in logs" :key="log.id" class="flex items-center justify-between py-3">
                            <div class="flex items-center gap-3">
                                <span class="font-medium text-gray-800">{{ log.food_item?.name }}</span>
                                <span class="text-sm text-gray-400">× {{ log.quantity }} {{ log.food_item?.unit }}</span>
                            </div>
                            <div class="flex items-center gap-4 text-sm">
                                <span v-if="parseFloat(log.amount_spent) > 0" class="text-yellow-600 font-medium">
                                    ₹{{ log.amount_spent }}
                                </span>
                                <span class="text-gray-400">
                                    ~{{ (log.food_item?.calories || 0) * log.quantity }} kcal
                                </span>
                                <button
                                    @click="askRemove(log)"
                                    class="text-red-400 hover:text-red-600 text-xs font-medium"
                                >
                                    Remove
                                </button>
                            </div>
                        </li>
                    </ul>
                </div>

                <!-- End-of-day hint -->
                <div v-if="logs.length > 0" class="bg-blue-50 border border-blue-200 rounded-lg p-4 text-sm text-blue-700">
                    Your daily report will be sent to you at 9 PM. Check your <Link :href="route('notifications')" class="font-medium underline">notifications</Link> for health insights.
                </div>

            </div>
        </div>

        <!-- Confirmation Modal -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition ease-out duration-150"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition ease-in duration-100"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div
                    v-if="confirmTarget"
                    class="fixed inset-0 z-50 flex items-center justify-center p-4"
                    @click.self="cancelRemove"
                >
                    <!-- Backdrop -->
                    <div class="absolute inset-0 bg-black/40"></div>

                    <!-- Dialog -->
                    <Transition
                        enter-active-class="transition ease-out duration-150"
                        enter-from-class="opacity-0 scale-95"
                        enter-to-class="opacity-100 scale-100"
                        leave-active-class="transition ease-in duration-100"
                        leave-from-class="opacity-100 scale-100"
                        leave-to-class="opacity-0 scale-95"
                    >
                        <div v-if="confirmTarget" class="relative bg-white rounded-xl shadow-xl w-full max-w-sm p-6">
                            <!-- Icon -->
                            <div class="flex items-center justify-center w-12 h-12 rounded-full bg-red-100 mx-auto mb-4">
                                <svg class="w-6 h-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </div>

                            <h3 class="text-center text-lg font-semibold text-gray-800 mb-1">Remove entry?</h3>
                            <p class="text-center text-sm text-gray-500 mb-6">
                                <span class="font-medium text-gray-700">{{ confirmTarget.name }}</span>
                                will be removed from your log.
                            </p>

                            <div class="flex gap-3">
                                <button
                                    @click="cancelRemove"
                                    class="flex-1 px-4 py-2 rounded-lg border border-gray-300 text-sm font-medium text-gray-700 hover:bg-gray-50 transition"
                                >
                                    Cancel
                                </button>
                                <button
                                    @click="confirmRemove"
                                    :disabled="removing"
                                    class="flex-1 px-4 py-2 rounded-lg bg-red-500 text-white text-sm font-medium hover:bg-red-600 disabled:opacity-60 transition"
                                >
                                    {{ removing ? 'Removing…' : 'Yes, Remove' }}
                                </button>
                            </div>
                        </div>
                    </Transition>
                </div>
            </Transition>
        </Teleport>

    </AuthenticatedLayout>
</template>
