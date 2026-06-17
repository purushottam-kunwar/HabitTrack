<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';

function getMonday(d) {
    const date = new Date(d);
    const day = date.getDay();
    const diff = date.getDate() - day + (day === 0 ? -6 : 1);
    date.setDate(diff);
    return date.toISOString().split('T')[0];
}

function getSunday(d) {
    const monday = new Date(getMonday(d));
    monday.setDate(monday.getDate() + 6);
    return monday.toISOString().split('T')[0];
}

const today = new Date().toISOString().split('T')[0];
const weekStart = ref(getMonday(today));
const weekEnd = ref(getSunday(today));
const report = ref(null);
const loading = ref(false);

const scoreColor = computed(() => {
    if (!report.value) return 'text-gray-400';
    if (report.value.health_score >= 70) return 'text-green-600';
    if (report.value.health_score >= 40) return 'text-yellow-500';
    return 'text-red-500';
});

const scoreLabel = computed(() => {
    if (!report.value) return '';
    if (report.value.health_score >= 70) return 'Great week!';
    if (report.value.health_score >= 40) return 'Could be better';
    return 'Needs improvement';
});

async function fetchReport() {
    loading.value = true;
    report.value = null;
    const res = await axios.get('/api/report/weekly', {
        params: { start_date: weekStart.value, end_date: weekEnd.value },
    });
    report.value = res.data;
    loading.value = false;
}

function prevWeek() {
    const d = new Date(weekStart.value);
    d.setDate(d.getDate() - 7);
    weekStart.value = d.toISOString().split('T')[0];
    weekEnd.value = getSunday(weekStart.value);
    fetchReport();
}

function nextWeek() {
    const d = new Date(weekStart.value);
    d.setDate(d.getDate() + 7);
    weekStart.value = d.toISOString().split('T')[0];
    weekEnd.value = getSunday(weekStart.value);
    fetchReport();
}

const dayNames = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];

function weekDays() {
    const days = [];
    const start = new Date(weekStart.value);
    for (let i = 0; i < 7; i++) {
        const d = new Date(start);
        d.setDate(start.getDate() + i);
        days.push(d.toISOString().split('T')[0]);
    }
    return days;
}

onMounted(fetchReport);
</script>

<template>
    <Head title="Weekly Report" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Weekly Report</h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8 space-y-6">

                <!-- Week Navigator -->
                <div class="bg-white shadow-sm rounded-lg p-4 flex items-center justify-between">
                    <button
                        @click="prevWeek"
                        class="text-sm px-3 py-1.5 border border-gray-300 rounded-md hover:bg-gray-50"
                    >
                        ← Prev Week
                    </button>
                    <div class="text-center">
                        <p class="font-semibold text-gray-800">{{ weekStart }} to {{ weekEnd }}</p>
                        <p class="text-xs text-gray-400 mt-0.5">Mon – Sun</p>
                    </div>
                    <button
                        @click="nextWeek"
                        :disabled="weekStart >= getMonday(today)"
                        class="text-sm px-3 py-1.5 border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-40"
                    >
                        Next Week →
                    </button>
                </div>

                <div v-if="loading" class="text-center text-gray-400 py-16">Loading report...</div>

                <template v-else-if="report">

                    <!-- Health Score -->
                    <div class="bg-white shadow-sm rounded-lg p-8 text-center">
                        <p class="text-sm text-gray-500 uppercase tracking-wide mb-2">Health Score</p>
                        <p :class="scoreColor" class="text-7xl font-bold">{{ report.health_score }}<span class="text-3xl">%</span></p>
                        <p :class="scoreColor" class="mt-2 text-lg font-medium">{{ scoreLabel }}</p>
                        <p class="text-sm text-gray-400 mt-1">Based on {{ report.total_items }} logged items</p>
                    </div>

                    <!-- Summary Cards -->
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                        <div class="bg-white rounded-lg shadow-sm p-5 text-center border-l-4 border-blue-500">
                            <p class="text-3xl font-bold text-blue-600">{{ report.total_items }}</p>
                            <p class="text-xs text-gray-500 mt-1">Total Items</p>
                        </div>
                        <div class="bg-white rounded-lg shadow-sm p-5 text-center border-l-4 border-green-500">
                            <p class="text-3xl font-bold text-green-600">{{ report.healthy_count }}</p>
                            <p class="text-xs text-gray-500 mt-1">Healthy</p>
                        </div>
                        <div class="bg-white rounded-lg shadow-sm p-5 text-center border-l-4 border-red-500">
                            <p class="text-3xl font-bold text-red-500">{{ report.unhealthy_count }}</p>
                            <p class="text-xs text-gray-500 mt-1">Unhealthy</p>
                        </div>
                        <div class="bg-white rounded-lg shadow-sm p-5 text-center border-l-4 border-yellow-500">
                            <p class="text-3xl font-bold text-yellow-600">₹{{ parseFloat(report.total_spent).toFixed(2) }}</p>
                            <p class="text-xs text-gray-500 mt-1">Total Spent</p>
                        </div>
                    </div>

                    <!-- Day-by-Day Breakdown -->
                    <div class="bg-white shadow-sm rounded-lg p-6">
                        <h3 class="font-semibold text-gray-700 mb-4">Day-by-Day Breakdown</h3>
                        <div class="grid grid-cols-7 gap-2">
                            <div
                                v-for="(day, idx) in weekDays()"
                                :key="day"
                                class="text-center"
                            >
                                <p class="text-xs font-medium text-gray-400 mb-2">{{ dayNames[idx] }}</p>
                                <div class="bg-gray-50 rounded-lg p-2 min-h-[90px] flex flex-col items-center justify-center">
                                    <template v-if="report.by_day[day]">
                                        <p class="text-green-600 font-bold text-lg">{{ report.by_day[day].healthy }}</p>
                                        <p class="text-xs text-gray-400">healthy</p>
                                        <p class="text-red-500 font-bold text-lg mt-1">{{ report.by_day[day].unhealthy }}</p>
                                        <p class="text-xs text-gray-400">unhealthy</p>
                                        <p v-if="report.by_day[day].spent > 0" class="text-xs text-yellow-600 mt-1">
                                            ₹{{ parseFloat(report.by_day[day].spent).toFixed(0) }}
                                        </p>
                                    </template>
                                    <p v-else class="text-xs text-gray-300">—</p>
                                </div>
                                <p
                                    :class="day === today ? 'bg-green-600 text-white' : 'text-gray-300'"
                                    class="text-xs mt-1 rounded px-1"
                                >{{ day.slice(8) }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Spending by Category -->
                    <div class="bg-white shadow-sm rounded-lg p-6">
                        <h3 class="font-semibold text-gray-700 mb-4">Spending by Category</h3>
                        <div class="space-y-4">
                            <div>
                                <div class="flex justify-between text-sm mb-1">
                                    <span class="text-green-600 font-medium">Healthy Food</span>
                                    <span>₹{{ parseFloat(report.spent_by_category.healthy || 0).toFixed(2) }}</span>
                                </div>
                                <div class="w-full bg-gray-100 rounded-full h-3">
                                    <div
                                        class="bg-green-500 h-3 rounded-full transition-all"
                                        :style="{
                                            width: report.total_spent > 0
                                                ? ((report.spent_by_category.healthy / report.total_spent) * 100) + '%'
                                                : '0%'
                                        }"
                                    ></div>
                                </div>
                            </div>
                            <div>
                                <div class="flex justify-between text-sm mb-1">
                                    <span class="text-red-500 font-medium">Unhealthy Food</span>
                                    <span>₹{{ parseFloat(report.spent_by_category.unhealthy || 0).toFixed(2) }}</span>
                                </div>
                                <div class="w-full bg-gray-100 rounded-full h-3">
                                    <div
                                        class="bg-red-400 h-3 rounded-full transition-all"
                                        :style="{
                                            width: report.total_spent > 0
                                                ? ((report.spent_by_category.unhealthy / report.total_spent) * 100) + '%'
                                                : '0%'
                                        }"
                                    ></div>
                                </div>
                            </div>
                        </div>
                    </div>

                </template>

                <div v-else class="text-center text-gray-400 py-16">No data available for this week.</div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
