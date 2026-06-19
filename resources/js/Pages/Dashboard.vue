<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, Link } from "@inertiajs/vue3";
import { ref, onMounted, computed } from "vue";
import axios from "axios";

const today = new Date().toISOString().split("T")[0];
const selectedDate = ref(today);
const logs = ref([]);
const loading = ref(false);
const stats = ref(null);

const confirmTarget = ref(null);
const removing = ref(false);

// Water tracking
const water = ref({ glass_count: 0, amount_ml: 0, target_glasses: 8, target_ml: 2000, percent: 0 });
const waterLoading = ref(false);

// Daily challenges
const challenges = ref([]);
const challengesLoading = ref(false);

// Weight tracking
const weightInput = ref('');
const weightLogs = ref({ logs: [], current_weight: null, change_30d: null });
const weightSaving = ref(false);
const showWeightForm = ref(false);

// Budget tracking
const budget = ref({ daily_budget: null, today_spent: 0, percent: null, remaining: null, last_7_days: [] });
const budgetInput = ref('');
const budgetSaving = ref(false);
const showBudgetForm = ref(false);

async function fetchBudget() {
    try {
        const res = await axios.get('/api/budget');
        budget.value = res.data;
        if (res.data.daily_budget) budgetInput.value = res.data.daily_budget;
    } catch (e) {
        console.error('Failed to fetch budget:', e);
    }
}

async function saveBudget() {
    if (!budgetInput.value) return;
    budgetSaving.value = true;
    try {
        await axios.post('/api/budget', { daily_budget: budgetInput.value });
        await fetchBudget();
        showBudgetForm.value = false;
    } finally {
        budgetSaving.value = false;
    }
}

const budgetBarColor = computed(() => {
    const p = budget.value.percent;
    if (p === null) return 'bg-gray-300';
    if (p >= 100) return 'bg-red-500';
    if (p >= 75) return 'bg-yellow-400';
    return 'bg-green-500';
});

const budgetStatus = computed(() => {
    if (!budget.value.daily_budget) return null;
    if (budget.value.remaining < 0) return { text: `₹${Math.abs(budget.value.remaining).toFixed(2)} over budget`, cls: 'text-red-600' };
    if (budget.value.percent >= 75) return { text: `₹${budget.value.remaining.toFixed(2)} left — watch out`, cls: 'text-yellow-600' };
    return { text: `₹${budget.value.remaining.toFixed(2)} remaining`, cls: 'text-green-600' };
});

const maxLast7Spent = computed(() => Math.max(...budget.value.last_7_days.map(d => d.spent), 1));

// Mood tracking
const MOODS = [
    { value: 1, emoji: '😞', label: 'Awful' },
    { value: 2, emoji: '😕', label: 'Bad' },
    { value: 3, emoji: '😐', label: 'Okay' },
    { value: 4, emoji: '😊', label: 'Good' },
    { value: 5, emoji: '😄', label: 'Great' },
];
const ENERGY = [
    { value: 1, emoji: '🪫', label: 'Drained' },
    { value: 2, emoji: '😴', label: 'Tired' },
    { value: 3, emoji: '🙂', label: 'Normal' },
    { value: 4, emoji: '⚡', label: 'Energized' },
    { value: 5, emoji: '🚀', label: 'Pumped' },
];
const mood = ref({ today: null, selected: null, energy: null, notes: '', saving: false, saved: false });

async function fetchMoodToday() {
    try {
        const res = await axios.get('/api/mood/today');
        if (res.data) {
            mood.value.today = res.data;
            mood.value.selected = res.data.mood;
            mood.value.energy = res.data.energy_level;
            mood.value.notes = res.data.notes || '';
        }
    } catch (e) {
        console.error('Failed to fetch mood:', e);
    }
}

async function saveMood() {
    if (!mood.value.selected || !mood.value.energy) return;
    mood.value.saving = true;
    try {
        const res = await axios.post('/api/mood', {
            mood: mood.value.selected,
            energy_level: mood.value.energy,
            notes: mood.value.notes || null,
        });
        mood.value.today = res.data;
        mood.value.saved = true;
        setTimeout(() => { mood.value.saved = false; }, 2000);
    } finally {
        mood.value.saving = false;
    }
}

// AI Health Coach
const aiCoach = ref({ suggestion: null, loading: false, fetched: false });

const totalSpent = computed(() =>
    logs.value
        .reduce((sum, l) => sum + parseFloat(l.amount_spent || 0), 0)
        .toFixed(2),
);
const totalCalories = computed(() =>
    logs.value.reduce(
        (sum, l) => sum + (l.food_item?.calories || 0) * l.quantity,
        0,
    ),
);

const completedChallenges = computed(() => challenges.value.filter((c) => c.completed).length);

async function fetchLogs() {
    loading.value = true;
    const res = await axios.get("/api/habit-logs", {
        params: { date: selectedDate.value },
    });
    logs.value = res.data;
    loading.value = false;
    if (selectedDate.value === today) {
        fetchChallenges();
    }
}

async function fetchStats() {
    try {
        const res = await axios.get("/api/stats");
        stats.value = res.data;
    } catch (e) {
        console.error("Failed to fetch stats:", e);
    }
}

async function fetchWater() {
    try {
        const res = await axios.get("/api/water/today");
        water.value = res.data;
    } catch (e) {
        console.error("Failed to fetch water:", e);
    }
}

async function addGlass() {
    waterLoading.value = true;
    try {
        const res = await axios.post("/api/water/add");
        water.value = res.data;
        fetchChallenges();
    } finally {
        waterLoading.value = false;
    }
}

async function fetchChallenges() {
    challengesLoading.value = true;
    try {
        const res = await axios.get("/api/challenges/today");
        challenges.value = res.data;
    } finally {
        challengesLoading.value = false;
    }
}

async function fetchWeight() {
    try {
        const res = await axios.get("/api/weight");
        weightLogs.value = res.data;
        if (res.data.current_weight) {
            weightInput.value = res.data.current_weight;
        }
    } catch (e) {
        console.error("Failed to fetch weight:", e);
    }
}

async function fetchAiCoach() {
    aiCoach.value.loading = true;
    aiCoach.value.fetched = false;
    try {
        const res = await axios.get("/api/ai-coach/daily");
        aiCoach.value.suggestion = res.data.suggestion;
        aiCoach.value.fetched = true;
    } catch (e) {
        aiCoach.value.suggestion = "Couldn't load coaching right now. Try again later.";
        aiCoach.value.fetched = true;
    } finally {
        aiCoach.value.loading = false;
    }
}

async function saveWeight() {
    if (!weightInput.value) return;
    weightSaving.value = true;
    try {
        await axios.post("/api/weight", { weight_kg: weightInput.value });
        await fetchWeight();
        showWeightForm.value = false;
    } finally {
        weightSaving.value = false;
    }
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
        logs.value = logs.value.filter((l) => l.id !== confirmTarget.value.id);
        fetchChallenges();
    } finally {
        confirmTarget.value = null;
        removing.value = false;
    }
}

onMounted(async () => {
    await Promise.all([fetchLogs(), fetchStats(), fetchWater(), fetchWeight(), fetchMoodToday(), fetchBudget()]);
    fetchChallenges();
});
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Daily Food Log
            </h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-6">
                <!-- Date Picker -->
                <div
                    class="bg-white shadow-sm rounded-lg p-6 flex flex-wrap items-center gap-4"
                >
                    <label class="font-medium text-gray-700"
                        >Select Date:</label
                    >
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
                    <div
                        class="bg-white rounded-lg shadow-sm p-5 text-center border-l-4 border-blue-500"
                    >
                        <p class="text-3xl font-bold text-blue-600">
                            {{ logs.length }}
                        </p>
                        <p class="text-sm text-gray-500 mt-1">Items Logged</p>
                    </div>
                    <div
                        class="bg-white rounded-lg shadow-sm p-5 text-center border-l-4 border-orange-400"
                    >
                        <p class="text-3xl font-bold text-orange-500">
                            {{ totalCalories }}
                        </p>
                        <p class="text-sm text-gray-500 mt-1">Est. Calories</p>
                    </div>
                    <div
                        class="bg-white rounded-lg shadow-sm p-5 text-center border-l-4 border-yellow-500"
                    >
                        <p class="text-3xl font-bold text-yellow-600">
                            ₹{{ totalSpent }}
                        </p>
                        <p class="text-sm text-gray-500 mt-1">Spent Today</p>
                    </div>
                </div>

                <!-- Streaks Cards -->
                <div v-if="stats" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div
                        class="bg-white rounded-lg shadow-sm p-5 border-l-4 border-red-500"
                    >
                        <p class="text-2xl font-bold text-red-600">
                            🔥 {{ stats.streaks.logging }}
                        </p>
                        <p class="text-sm text-gray-500 mt-1">
                            Day Logging Streak
                        </p>
                        <p class="text-xs text-gray-400 mt-1">
                            Best: {{ stats.streaks.logging_best }}
                        </p>
                    </div>
                    <div
                        class="bg-white rounded-lg shadow-sm p-5 border-l-4 border-green-500"
                    >
                        <p class="text-2xl font-bold text-green-600">
                            🥗 {{ stats.streaks.healthy }}
                        </p>
                        <p class="text-sm text-gray-500 mt-1">Healthy Days</p>
                        <p class="text-xs text-gray-400 mt-1">
                            Best: {{ stats.streaks.healthy_best }}
                        </p>
                    </div>
                    <div
                        class="bg-white rounded-lg shadow-sm p-5 border-l-4 border-blue-500"
                    >
                        <p class="text-2xl font-bold text-blue-600">
                            📊 {{ stats.streaks.consistency }}%
                        </p>
                        <p class="text-sm text-gray-500 mt-1">
                            30-Day Consistency
                        </p>
                        <p class="text-xs text-gray-400 mt-1">
                            Days logged this month
                        </p>
                    </div>
                </div>

                <!-- XP & Level Section -->
                <div v-if="stats" class="bg-white rounded-lg shadow-sm p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-semibold text-gray-700">
                            Level {{ stats.xp.level }}
                        </h3>
                        <span class="text-sm text-gray-500"
                            >{{ stats.xp.total }} Total XP</span
                        >
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-4">
                        <div
                            class="bg-green-600 h-4 rounded-full transition-all"
                            :style="{ width: stats.xp.progress_percent + '%' }"
                        ></div>
                    </div>
                    <p class="text-xs text-gray-500 mt-2">
                        {{ stats.xp.xp_in_level }} / 500 XP to Level
                        {{ stats.xp.level + 1 }}
                    </p>
                </div>

                <!-- Achievements Grid -->
                <div
                    v-if="stats && stats.achievements.length > 0"
                    class="bg-white rounded-lg shadow-sm p-6"
                >
                    <h3 class="font-semibold text-gray-700 mb-4">
                        Achievements ({{ stats.achievements.length }})
                    </h3>
                    <div
                        class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4"
                    >
                        <div
                            v-for="achievement in stats.achievements"
                            :key="achievement.slug"
                            class="flex flex-col items-center p-4 bg-yellow-50 rounded-lg border border-yellow-200"
                        >
                            <span class="text-3xl mb-2">{{
                                achievement.icon
                            }}</span>
                            <p
                                class="text-xs font-medium text-center text-gray-700 line-clamp-2"
                            >
                                {{ achievement.name }}
                            </p>
                            <p class="text-xs text-gray-400 mt-2">
                                {{ achievement.unlocked_at }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Daily Challenges + Water + Weight row -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <!-- Daily Challenges -->
                    <div class="bg-white rounded-lg shadow-sm p-6 md:col-span-2">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="font-semibold text-gray-700">Today's Challenges</h3>
                            <span v-if="challenges.length" class="text-xs text-gray-400">
                                {{ completedChallenges }} / {{ challenges.length }} done
                            </span>
                        </div>
                        <div v-if="challengesLoading" class="text-sm text-gray-400">Checking progress…</div>
                        <ul v-else class="space-y-3">
                            <li
                                v-for="c in challenges"
                                :key="c.id"
                                class="flex items-center gap-3"
                            >
                                <span
                                    class="flex-shrink-0 w-6 h-6 rounded-full flex items-center justify-center text-sm"
                                    :class="c.completed ? 'bg-green-100 text-green-600' : 'bg-gray-100 text-gray-400'"
                                >
                                    {{ c.completed ? '✓' : '○' }}
                                </span>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-700" :class="c.completed ? 'line-through text-gray-400' : ''">
                                        {{ c.name }}
                                    </p>
                                    <p v-if="c.daily_target" class="text-xs text-gray-400">
                                        {{ c.current_progress }} / {{ c.daily_target }} {{ c.unit }}
                                    </p>
                                </div>
                                <span class="text-xs text-green-600 font-medium flex-shrink-0">+{{ c.xp_reward }} XP</span>
                            </li>
                        </ul>
                    </div>

                    <!-- Water Tracker -->
                    <div class="bg-white rounded-lg shadow-sm p-6 flex flex-col">
                        <h3 class="font-semibold text-gray-700 mb-3">Water Intake</h3>
                        <div class="flex-1 flex flex-col items-center justify-center">
                            <div class="relative w-24 h-24 mb-3">
                                <svg class="w-24 h-24 -rotate-90" viewBox="0 0 36 36">
                                    <circle cx="18" cy="18" r="15.9" fill="none" stroke="#e5e7eb" stroke-width="3"/>
                                    <circle
                                        cx="18" cy="18" r="15.9" fill="none"
                                        stroke="#3b82f6" stroke-width="3"
                                        stroke-dasharray="100"
                                        :stroke-dashoffset="100 - water.percent"
                                        stroke-linecap="round"
                                        style="transition: stroke-dashoffset 0.4s ease"
                                    />
                                </svg>
                                <div class="absolute inset-0 flex flex-col items-center justify-center">
                                    <span class="text-xl font-bold text-blue-600">{{ water.glass_count }}</span>
                                    <span class="text-xs text-gray-400">/ 8</span>
                                </div>
                            </div>
                            <p class="text-xs text-gray-500 mb-4">{{ water.amount_ml }} ml / 2000 ml</p>
                            <button
                                @click="addGlass"
                                :disabled="waterLoading"
                                class="px-4 py-1.5 rounded-lg bg-blue-500 text-white text-sm hover:bg-blue-600 disabled:opacity-60 transition"
                            >+ Glass</button>
                        </div>
                    </div>
                </div>

                <!-- Weight Tracker -->
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-semibold text-gray-700">Weight</h3>
                        <button
                            @click="showWeightForm = !showWeightForm"
                            class="text-xs text-green-600 hover:underline"
                        >
                            {{ showWeightForm ? 'Cancel' : 'Log Weight' }}
                        </button>
                    </div>
                    <div v-if="showWeightForm" class="flex gap-3 mb-4">
                        <input
                            v-model="weightInput"
                            type="number"
                            step="0.1"
                            min="20"
                            max="300"
                            placeholder="e.g. 72.5"
                            class="border border-gray-300 rounded-md px-3 py-2 text-sm w-32 focus:outline-none focus:ring-2 focus:ring-green-500"
                        />
                        <span class="self-center text-sm text-gray-500">kg</span>
                        <button
                            @click="saveWeight"
                            :disabled="weightSaving || !weightInput"
                            class="px-4 py-2 bg-green-600 text-white text-sm rounded-md hover:bg-green-700 disabled:opacity-60 transition"
                        >
                            {{ weightSaving ? 'Saving…' : 'Save' }}
                        </button>
                    </div>
                    <div v-if="weightLogs.current_weight" class="flex items-center gap-6">
                        <div>
                            <p class="text-3xl font-bold text-gray-800">{{ weightLogs.current_weight }} <span class="text-base font-normal text-gray-400">kg</span></p>
                            <p class="text-xs text-gray-400 mt-1">Current weight</p>
                        </div>
                        <div v-if="weightLogs.change_30d !== null">
                            <p
                                class="text-xl font-semibold"
                                :class="weightLogs.change_30d < 0 ? 'text-green-600' : weightLogs.change_30d > 0 ? 'text-red-500' : 'text-gray-500'"
                            >
                                {{ weightLogs.change_30d > 0 ? '+' : '' }}{{ weightLogs.change_30d }} kg
                            </p>
                            <p class="text-xs text-gray-400 mt-1">Last 30 days</p>
                        </div>
                    </div>
                    <p v-else class="text-sm text-gray-400">No weight logged yet. Click "Log Weight" to start tracking.</p>
                </div>

                <!-- Budget Tracker -->
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-semibold text-gray-700">Daily Budget</h3>
                        <button @click="showBudgetForm = !showBudgetForm" class="text-xs text-green-600 hover:underline">
                            {{ showBudgetForm ? 'Cancel' : (budget.daily_budget ? 'Edit Budget' : 'Set Budget') }}
                        </button>
                    </div>

                    <!-- Set / edit form -->
                    <div v-if="showBudgetForm" class="flex gap-3 mb-4">
                        <span class="self-center text-sm text-gray-500">₹</span>
                        <input
                            v-model="budgetInput"
                            type="number"
                            min="1"
                            step="1"
                            placeholder="e.g. 200"
                            class="border border-gray-300 rounded-md px-3 py-2 text-sm w-32 focus:outline-none focus:ring-2 focus:ring-green-500"
                        />
                        <span class="self-center text-sm text-gray-400">per day</span>
                        <button
                            @click="saveBudget"
                            :disabled="budgetSaving || !budgetInput"
                            class="px-4 py-2 bg-green-600 text-white text-sm rounded-md hover:bg-green-700 disabled:opacity-60 transition"
                        >
                            {{ budgetSaving ? 'Saving…' : 'Save' }}
                        </button>
                    </div>

                    <!-- No budget set -->
                    <div v-if="!budget.daily_budget && !showBudgetForm" class="text-center py-6">
                        <p class="text-gray-400 text-sm">No daily budget set.</p>
                        <button @click="showBudgetForm = true" class="mt-2 text-sm text-green-600 hover:underline">Set a budget to track your spending</button>
                    </div>

                    <!-- Budget progress -->
                    <template v-if="budget.daily_budget">
                        <div class="flex items-baseline justify-between mb-2">
                            <div>
                                <span class="text-2xl font-bold text-gray-800">₹{{ budget.today_spent.toFixed(2) }}</span>
                                <span class="text-sm text-gray-400 ml-1">/ ₹{{ budget.daily_budget }}</span>
                            </div>
                            <span :class="budgetStatus?.cls" class="text-sm font-medium">{{ budgetStatus?.text }}</span>
                        </div>

                        <!-- Progress bar -->
                        <div class="w-full bg-gray-100 rounded-full h-3 mb-4 overflow-hidden">
                            <div
                                :class="budgetBarColor"
                                class="h-3 rounded-full transition-all"
                                :style="{ width: Math.min(budget.percent ?? 0, 100) + '%' }"
                            ></div>
                        </div>

                        <!-- Overspend warning -->
                        <div v-if="budget.percent >= 100" class="bg-red-50 border border-red-200 rounded-lg px-4 py-2 mb-4 text-sm text-red-700">
                            You've exceeded today's budget. Consider skipping non-essential purchases.
                        </div>

                        <!-- 7-day mini bar chart -->
                        <div>
                            <p class="text-xs text-gray-400 mb-2">Last 7 days</p>
                            <div class="flex items-end gap-1 h-12">
                                <div
                                    v-for="day in budget.last_7_days"
                                    :key="day.date"
                                    class="flex-1 flex flex-col items-center gap-1"
                                >
                                    <div
                                        class="w-full rounded-t transition-all"
                                        :class="budget.daily_budget && day.spent > budget.daily_budget ? 'bg-red-400' : 'bg-green-400'"
                                        :style="{ height: Math.max((day.spent / maxLast7Spent) * 40, day.spent > 0 ? 4 : 0) + 'px' }"
                                        :title="`₹${day.spent} on ${day.date}`"
                                    ></div>
                                    <span class="text-xs text-gray-300" style="font-size:9px">{{ day.date.slice(8) }}</span>
                                </div>
                            </div>
                            <!-- Budget line indicator -->
                            <p class="text-xs text-gray-400 mt-1">
                                <span class="inline-block w-3 h-0.5 bg-red-400 mr-1 align-middle"></span>Red = over ₹{{ budget.daily_budget }} budget
                            </p>
                        </div>
                    </template>
                </div>

                <!-- Mood Tracker -->
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-semibold text-gray-700">How are you feeling today?</h3>
                        <span v-if="mood.today" class="text-xs text-gray-400">Logged ✓</span>
                    </div>

                    <!-- Mood picker -->
                    <p class="text-xs text-gray-500 mb-2 font-medium">Mood</p>
                    <div class="flex gap-2 mb-4">
                        <button
                            v-for="m in MOODS"
                            :key="m.value"
                            @click="mood.selected = m.value"
                            :class="mood.selected === m.value
                                ? 'ring-2 ring-indigo-500 bg-indigo-50 scale-110'
                                : 'bg-gray-50 hover:bg-gray-100'"
                            class="flex-1 flex flex-col items-center py-2 rounded-xl transition-all"
                        >
                            <span class="text-2xl">{{ m.emoji }}</span>
                            <span class="text-xs text-gray-500 mt-1 hidden sm:block">{{ m.label }}</span>
                        </button>
                    </div>

                    <!-- Energy picker -->
                    <p class="text-xs text-gray-500 mb-2 font-medium">Energy</p>
                    <div class="flex gap-2 mb-4">
                        <button
                            v-for="e in ENERGY"
                            :key="e.value"
                            @click="mood.energy = e.value"
                            :class="mood.energy === e.value
                                ? 'ring-2 ring-amber-500 bg-amber-50 scale-110'
                                : 'bg-gray-50 hover:bg-gray-100'"
                            class="flex-1 flex flex-col items-center py-2 rounded-xl transition-all"
                        >
                            <span class="text-2xl">{{ e.emoji }}</span>
                            <span class="text-xs text-gray-500 mt-1 hidden sm:block">{{ e.label }}</span>
                        </button>
                    </div>

                    <!-- Notes -->
                    <input
                        v-model="mood.notes"
                        type="text"
                        maxlength="300"
                        placeholder="Optional note (e.g. felt stressed at work)"
                        class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-400 mb-3"
                    />

                    <button
                        @click="saveMood"
                        :disabled="!mood.selected || !mood.energy || mood.saving"
                        class="w-full py-2 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 disabled:opacity-50 transition"
                    >
                        <span v-if="mood.saved">Saved!</span>
                        <span v-else-if="mood.saving">Saving…</span>
                        <span v-else>{{ mood.today ? 'Update Mood' : 'Save Mood' }}</span>
                    </button>
                </div>

                <!-- AI Health Coach -->
                <div class="bg-gradient-to-r from-violet-50 to-indigo-50 border border-indigo-200 rounded-lg p-6">
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center gap-2">
                            <span class="text-xl">🤖</span>
                            <h3 class="font-semibold text-indigo-800">AI Health Coach</h3>
                        </div>
                        <button
                            @click="fetchAiCoach"
                            :disabled="aiCoach.loading"
                            class="text-xs px-3 py-1.5 rounded-full bg-indigo-600 text-white hover:bg-indigo-700 disabled:opacity-60 transition flex items-center gap-1"
                        >
                            <span v-if="aiCoach.loading">Thinking…</span>
                            <span v-else>{{ aiCoach.fetched ? 'Refresh' : 'Get Coaching' }}</span>
                        </button>
                    </div>
                    <div v-if="aiCoach.loading" class="flex items-center gap-3 py-3">
                        <div class="animate-spin w-4 h-4 border-2 border-indigo-400 border-t-transparent rounded-full"></div>
                        <p class="text-sm text-indigo-500">Claude is analyzing your day…</p>
                    </div>
                    <p v-else-if="aiCoach.fetched" class="text-sm text-indigo-900 leading-relaxed">
                        {{ aiCoach.suggestion }}
                    </p>
                    <p v-else class="text-sm text-indigo-400">
                        Click "Get Coaching" to receive personalized health tips based on today's food log.
                    </p>
                </div>

                <!-- Log List -->
                <div class="bg-white shadow-sm rounded-lg p-6">
                    <h3 class="font-semibold text-gray-700 mb-4">
                        Food & Drink Log for
                        <span class="text-green-600">{{ selectedDate }}</span>
                    </h3>

                    <div v-if="loading" class="text-center text-gray-400 py-8">
                        Loading...
                    </div>

                    <div
                        v-else-if="logs.length === 0"
                        class="text-center py-12"
                    >
                        <p class="text-gray-400 text-lg">
                            No entries for this date.
                        </p>
                        <Link
                            :href="route('log')"
                            class="mt-3 inline-block text-green-600 hover:underline text-sm"
                        >
                            + Add your first entry
                        </Link>
                    </div>

                    <ul v-else class="divide-y divide-gray-100">
                        <li
                            v-for="log in logs"
                            :key="log.id"
                            class="flex items-center justify-between py-3"
                        >
                            <div class="flex items-center gap-3">
                                <span class="font-medium text-gray-800">{{
                                    log.food_item?.name
                                }}</span>
                                <span class="text-sm text-gray-400"
                                    >× {{ log.quantity }}
                                    {{ log.food_item?.unit }}</span
                                >
                            </div>
                            <div class="flex items-center gap-4 text-sm">
                                <span
                                    v-if="parseFloat(log.amount_spent) > 0"
                                    class="text-yellow-600 font-medium"
                                >
                                    ₹{{ log.amount_spent }}
                                </span>
                                <span class="text-gray-400">
                                    ~{{
                                        (log.food_item?.calories || 0) *
                                        log.quantity
                                    }}
                                    kcal
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
                <div
                    v-if="logs.length > 0"
                    class="bg-blue-50 border border-blue-200 rounded-lg p-4 text-sm text-blue-700"
                >
                    Your daily report will be sent to you at 9 PM. Check your
                    <Link
                        :href="route('notifications')"
                        class="font-medium underline"
                        >notifications</Link
                    >
                    for health insights.
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
                        <div
                            v-if="confirmTarget"
                            class="relative bg-white rounded-xl shadow-xl w-full max-w-sm p-6"
                        >
                            <!-- Icon -->
                            <div
                                class="flex items-center justify-center w-12 h-12 rounded-full bg-red-100 mx-auto mb-4"
                            >
                                <svg
                                    class="w-6 h-6 text-red-500"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                                    />
                                </svg>
                            </div>

                            <h3
                                class="text-center text-lg font-semibold text-gray-800 mb-1"
                            >
                                Remove entry?
                            </h3>
                            <p class="text-center text-sm text-gray-500 mb-6">
                                <span class="font-medium text-gray-700">{{
                                    confirmTarget.name
                                }}</span>
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
                                    {{ removing ? "Removing…" : "Yes, Remove" }}
                                </button>
                            </div>
                        </div>
                    </Transition>
                </div>
            </Transition>
        </Teleport>
    </AuthenticatedLayout>
</template>
