<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head } from "@inertiajs/vue3";
import { ref, onMounted, computed } from "vue";
import axios from "axios";

const filter = ref("global");
const rankings = ref([]);
const loading = ref(false);

const searchQuery = ref("");
const searchResults = ref([]);
const searchLoading = ref(false);
let searchTimeout = null;

const RANK_ICONS = { 1: "🥇", 2: "🥈", 3: "🥉" };

const myRank = computed(() => {
    const idx = rankings.value.findIndex((u) => u.is_me);
    return idx === -1 ? null : idx + 1;
});

async function fetchLeaderboard() {
    loading.value = true;
    try {
        const res = await axios.get("/api/leaderboard", {
            params: { filter: filter.value },
        });
        rankings.value = res.data;
    } finally {
        loading.value = false;
    }
}

function switchFilter(f) {
    filter.value = f;
    fetchLeaderboard();
}

function onSearchInput() {
    clearTimeout(searchTimeout);
    if (searchQuery.value.length < 2) {
        searchResults.value = [];
        return;
    }
    searchTimeout = setTimeout(async () => {
        searchLoading.value = true;
        try {
            const res = await axios.get("/api/users/search", {
                params: { q: searchQuery.value },
            });
            searchResults.value = res.data;
        } finally {
            searchLoading.value = false;
        }
    }, 350);
}

async function toggleFollow(user) {
    if (user.is_following) {
        await axios.delete(`/api/follow/${user.id}`);
        user.is_following = false;
    } else {
        await axios.post(`/api/follow/${user.id}`);
        user.is_following = true;
    }
    // Refresh rankings so friends tab stays in sync
    if (filter.value === "friends") fetchLeaderboard();
}

async function toggleFollowInRanking(user) {
    if (user.is_following) {
        await axios.delete(`/api/follow/${user.id}`);
        user.is_following = false;
    } else {
        await axios.post(`/api/follow/${user.id}`);
        user.is_following = true;
    }
    if (filter.value === "friends") fetchLeaderboard();
}

onMounted(fetchLeaderboard);
</script>

<template>
    <Head title="Leaderboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Leaderboard
            </h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8 space-y-6">

                <!-- Filter tabs + My rank badge -->
                <div class="bg-white rounded-lg shadow-sm p-4 flex items-center justify-between">
                    <div class="flex gap-2">
                        <button
                            @click="switchFilter('global')"
                            :class="filter === 'global'
                                ? 'bg-green-600 text-white'
                                : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                            class="px-4 py-2 rounded-lg text-sm font-medium transition"
                        >
                            🌍 Global
                        </button>
                        <button
                            @click="switchFilter('friends')"
                            :class="filter === 'friends'
                                ? 'bg-green-600 text-white'
                                : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                            class="px-4 py-2 rounded-lg text-sm font-medium transition"
                        >
                            👥 Friends
                        </button>
                    </div>
                    <div v-if="myRank" class="text-sm text-gray-500">
                        Your rank: <span class="font-bold text-green-600">#{{ myRank }}</span>
                    </div>
                </div>

                <!-- User search -->
                <div class="bg-white rounded-lg shadow-sm p-5">
                    <p class="text-sm font-medium text-gray-700 mb-3">Find &amp; Follow Users</p>
                    <div class="relative">
                        <input
                            v-model="searchQuery"
                            @input="onSearchInput"
                            type="text"
                            placeholder="Search by name…"
                            class="w-full border border-gray-300 rounded-lg px-4 py-2 pr-10 text-sm focus:outline-none focus:ring-2 focus:ring-green-500"
                        />
                        <span v-if="searchLoading" class="absolute right-3 top-2.5 animate-spin text-gray-400">⟳</span>
                    </div>

                    <ul v-if="searchResults.length" class="mt-3 divide-y divide-gray-100">
                        <li
                            v-for="user in searchResults"
                            :key="user.id"
                            class="flex items-center justify-between py-2.5"
                        >
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-full bg-indigo-100 flex items-center justify-center text-sm font-bold text-indigo-700">
                                    {{ user.initials }}
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-800">{{ user.name }}</p>
                                    <p class="text-xs text-gray-400">Lv {{ user.level }} · {{ user.total_xp }} XP</p>
                                </div>
                            </div>
                            <button
                                @click="toggleFollow(user)"
                                :class="user.is_following
                                    ? 'border border-gray-300 text-gray-600 hover:bg-red-50 hover:text-red-500 hover:border-red-300'
                                    : 'bg-green-600 text-white hover:bg-green-700'"
                                class="text-xs px-3 py-1.5 rounded-lg font-medium transition"
                            >
                                {{ user.is_following ? "Unfollow" : "Follow" }}
                            </button>
                        </li>
                    </ul>
                    <p v-else-if="searchQuery.length >= 2 && !searchLoading" class="text-sm text-gray-400 mt-3">
                        No users found for "{{ searchQuery }}"
                    </p>
                </div>

                <!-- Rankings -->
                <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                    <div v-if="loading" class="text-center py-16 text-gray-400">
                        Loading…
                    </div>
                    <div v-else-if="rankings.length === 0" class="text-center py-16">
                        <p class="text-4xl mb-3">👥</p>
                        <p class="text-gray-400 text-sm">
                            {{ filter === 'friends' ? 'Follow some users to see a friends leaderboard.' : 'No data yet.' }}
                        </p>
                    </div>
                    <table v-else class="w-full text-sm">
                        <thead class="bg-gray-50 border-b border-gray-100">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs text-gray-400 font-medium w-10">#</th>
                                <th class="px-4 py-3 text-left text-xs text-gray-400 font-medium">Player</th>
                                <th class="px-4 py-3 text-center text-xs text-gray-400 font-medium">Level</th>
                                <th class="px-4 py-3 text-center text-xs text-gray-400 font-medium">XP</th>
                                <th class="px-4 py-3 text-center text-xs text-gray-400 font-medium">🔥 Streak</th>
                                <th class="px-4 py-3 text-center text-xs text-gray-400 font-medium">📊</th>
                                <th class="px-4 py-3 text-center text-xs text-gray-400 font-medium w-24"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr
                                v-for="(user, idx) in rankings"
                                :key="user.id"
                                :class="user.is_me ? 'bg-green-50' : 'hover:bg-gray-50'"
                                class="transition"
                            >
                                <!-- Rank -->
                                <td class="px-4 py-3 text-center font-bold">
                                    <span v-if="RANK_ICONS[idx + 1]" class="text-lg">{{ RANK_ICONS[idx + 1] }}</span>
                                    <span v-else class="text-gray-400">{{ idx + 1 }}</span>
                                </td>

                                <!-- Name + initials avatar -->
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold flex-shrink-0"
                                            :class="user.is_me ? 'bg-green-600 text-white' : 'bg-indigo-100 text-indigo-700'"
                                        >
                                            {{ user.initials }}
                                        </div>
                                        <span class="font-medium text-gray-800">
                                            {{ user.name }}
                                            <span v-if="user.is_me" class="text-xs text-green-600 font-normal ml-1">(you)</span>
                                        </span>
                                    </div>
                                </td>

                                <!-- Level -->
                                <td class="px-4 py-3 text-center">
                                    <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-yellow-100 text-yellow-700 text-xs font-bold">
                                        {{ user.level }}
                                    </span>
                                </td>

                                <!-- XP -->
                                <td class="px-4 py-3 text-center font-semibold text-gray-700">
                                    {{ user.total_xp.toLocaleString() }}
                                </td>

                                <!-- Logging streak -->
                                <td class="px-4 py-3 text-center font-semibold text-orange-500">
                                    {{ user.logging_streak }}d
                                </td>

                                <!-- Consistency -->
                                <td class="px-4 py-3 text-center text-gray-500 text-xs">
                                    {{ user.consistency }}%
                                </td>

                                <!-- Follow button -->
                                <td class="px-4 py-3 text-center">
                                    <button
                                        v-if="!user.is_me"
                                        @click="toggleFollowInRanking(user)"
                                        :class="user.is_following
                                            ? 'border border-gray-300 text-gray-500 hover:text-red-500 hover:border-red-300'
                                            : 'bg-green-100 text-green-700 hover:bg-green-200'"
                                        class="text-xs px-3 py-1 rounded-full font-medium transition"
                                    >
                                        {{ user.is_following ? "Unfollow" : "+ Follow" }}
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
