<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    notifications: Array,
});

function formatDate(dateStr) {
    return new Date(dateStr).toLocaleString('en-IN', {
        day: 'numeric', month: 'short', year: 'numeric',
        hour: '2-digit', minute: '2-digit',
    });
}

const grouped = computed(() => {
    const daily = props.notifications.filter(n => n.data.type === 'daily_report');
    const weekly = props.notifications.filter(n => n.data.type === 'weekly_report');
    return { daily, weekly };
});

function scoreColor(score) {
    if (score >= 70) return 'text-green-600';
    if (score >= 40) return 'text-yellow-500';
    return 'text-red-500';
}

function scoreBg(score) {
    if (score >= 70) return 'bg-green-50 border-green-200';
    if (score >= 40) return 'bg-yellow-50 border-yellow-200';
    return 'bg-red-50 border-red-200';
}
</script>

<template>
    <Head title="Notifications" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Notifications</h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8 space-y-8">

                <div v-if="notifications.length === 0" class="bg-white shadow-sm rounded-lg p-12 text-center">
                    <p class="text-gray-400 text-lg">No notifications yet.</p>
                    <p class="text-gray-400 text-sm mt-2">Daily reports are sent at 9 PM and weekly reports every Sunday at 9 PM.</p>
                    <Link :href="route('log')" class="mt-4 inline-block text-green-600 hover:underline text-sm">Start logging food</Link>
                </div>

                <template v-else>

                    <!-- Weekly Reports -->
                    <div v-if="grouped.weekly.length > 0">
                        <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-3">Weekly Reports</h3>
                        <div class="space-y-4">
                            <div
                                v-for="n in grouped.weekly"
                                :key="n.id"
                                :class="scoreBg(n.data.health_score)"
                                class="border rounded-lg p-5"
                            >
                                <div class="flex items-start justify-between gap-4">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-2 mb-1">
                                            <span class="text-xs font-medium text-gray-500 uppercase">Weekly Report</span>
                                            <span class="text-xs text-gray-400">{{ n.data.period?.start }} — {{ n.data.period?.end }}</span>
                                        </div>
                                        <p class="text-sm text-gray-700">{{ n.data.summary }}</p>

                                        <div class="mt-3 grid grid-cols-2 sm:grid-cols-4 gap-3">
                                            <div class="text-center">
                                                <p :class="scoreColor(n.data.health_score)" class="text-2xl font-bold">{{ n.data.health_score }}%</p>
                                                <p class="text-xs text-gray-400">Health Score</p>
                                            </div>
                                            <div class="text-center">
                                                <p class="text-2xl font-bold text-green-600">{{ n.data.healthy_count }}</p>
                                                <p class="text-xs text-gray-400">Healthy</p>
                                            </div>
                                            <div class="text-center">
                                                <p class="text-2xl font-bold text-red-500">{{ n.data.unhealthy_count }}</p>
                                                <p class="text-xs text-gray-400">Unhealthy</p>
                                            </div>
                                            <div class="text-center">
                                                <p class="text-2xl font-bold text-yellow-600">₹{{ parseFloat(n.data.total_spent).toFixed(0) }}</p>
                                                <p class="text-xs text-gray-400">Total Spent</p>
                                            </div>
                                        </div>

                                        <div class="mt-3 flex gap-4 text-xs text-gray-500">
                                            <span class="text-green-600">₹{{ parseFloat(n.data.spent_by_category?.healthy || 0).toFixed(2) }} on healthy</span>
                                            <span class="text-red-500">₹{{ parseFloat(n.data.spent_by_category?.unhealthy || 0).toFixed(2) }} on unhealthy</span>
                                        </div>
                                    </div>
                                </div>
                                <p class="text-xs text-gray-400 mt-3">{{ formatDate(n.created_at) }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Daily Reports -->
                    <div v-if="grouped.daily.length > 0">
                        <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-3">Daily Reports</h3>
                        <div class="space-y-3">
                            <div
                                v-for="n in grouped.daily"
                                :key="n.id"
                                :class="n.data.total_items === 0 ? 'bg-gray-50 border-gray-200' : scoreBg(n.data.health_score)"
                                class="border rounded-lg p-4"
                            >
                                <div class="flex items-start justify-between gap-4">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-2 mb-1">
                                            <span class="text-xs font-medium text-gray-500 uppercase">Daily Report</span>
                                            <span class="text-xs text-gray-400">{{ n.data.date }}</span>
                                        </div>
                                        <p class="text-sm text-gray-700">{{ n.data.summary }}</p>

                                        <div v-if="n.data.total_items > 0" class="mt-3 flex flex-wrap gap-4 text-sm">
                                            <span :class="scoreColor(n.data.health_score)" class="font-semibold">{{ n.data.health_score }}% health score</span>
                                            <span class="text-green-600">{{ n.data.healthy_count }} healthy</span>
                                            <span class="text-red-500">{{ n.data.unhealthy_count }} unhealthy</span>
                                            <span class="text-yellow-600">₹{{ parseFloat(n.data.total_spent).toFixed(2) }} spent</span>
                                        </div>
                                    </div>
                                </div>
                                <p class="text-xs text-gray-400 mt-2">{{ formatDate(n.created_at) }}</p>
                            </div>
                        </div>
                    </div>

                </template>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
