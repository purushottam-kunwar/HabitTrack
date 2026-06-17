<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import axios from 'axios';

const today = new Date().toISOString().split('T')[0];

const form = ref({
    food_item_id: '',
    log_date: today,
    quantity: 1,
    amount_spent: '',
    notes: '',
});

const searchQuery = ref('');
const searchResults = ref([]);
const selectedItem = ref(null);
const showDropdown = ref(false);
const searching = ref(false);
const submitting = ref(false);
const successMsg = ref('');
const errors = ref({});

let debounceTimer = null;

watch(searchQuery, (val) => {
    if (selectedItem.value && val === selectedItem.value.name) return;
    selectedItem.value = null;
    form.value.food_item_id = '';
    clearTimeout(debounceTimer);
    if (val.length < 2) {
        searchResults.value = [];
        showDropdown.value = false;
        return;
    }
    debounceTimer = setTimeout(async () => {
        searching.value = true;
        try {
            const res = await axios.get('/api/food-items', { params: { search: val } });
            searchResults.value = res.data;
            showDropdown.value = res.data.length > 0;
        } finally {
            searching.value = false;
        }
    }, 280);
});

function selectItem(item) {
    selectedItem.value = item;
    form.value.food_item_id = item.id;
    searchQuery.value = item.name;
    showDropdown.value = false;
    searchResults.value = [];
}

function clearSelection() {
    selectedItem.value = null;
    form.value.food_item_id = '';
    searchQuery.value = '';
    searchResults.value = [];
}

function onBlur() {
    setTimeout(() => { showDropdown.value = false; }, 150);
}

const estimatedCalories = computed(() => {
    if (!selectedItem.value || !form.value.quantity) return null;
    return (selectedItem.value.calories || 0) * form.value.quantity;
});

async function submit() {
    errors.value = {};
    submitting.value = true;
    try {
        await axios.post('/api/habit-logs', form.value);
        successMsg.value = 'Entry added successfully!';
        form.value = { food_item_id: '', log_date: today, quantity: 1, amount_spent: '', notes: '' };
        selectedItem.value = null;
        searchQuery.value = '';
        setTimeout(() => successMsg.value = '', 3000);
    } catch (e) {
        if (e.response?.status === 422) {
            errors.value = e.response.data.errors;
        }
    } finally {
        submitting.value = false;
    }
}
</script>

<template>
    <Head title="Log Food" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Log Food & Drink</h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
                <div class="bg-white shadow-sm rounded-lg p-6 space-y-6">

                    <!-- Success -->
                    <div v-if="successMsg" class="bg-green-50 border border-green-300 text-green-700 px-4 py-3 rounded-md text-sm">
                        {{ successMsg }}
                    </div>

                    <!-- Date -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Date</label>
                        <input
                            type="date"
                            v-model="form.log_date"
                            :max="today"
                            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500"
                        />
                        <p v-if="errors.log_date" class="text-red-500 text-xs mt-1">{{ errors.log_date[0] }}</p>
                    </div>

                    <!-- Food Search -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Search food and drinks <span class="text-red-500">*</span>
                        </label>

                        <!-- Search Input -->
                        <div class="relative">
                            <div class="relative flex items-center">
                                <span class="absolute left-3 text-gray-400 pointer-events-none">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11A6 6 0 1 1 5 11a6 6 0 0 1 12 0z"/>
                                    </svg>
                                </span>
                                <input
                                    type="text"
                                    v-model="searchQuery"
                                    @focus="showDropdown = searchResults.length > 0"
                                    @blur="onBlur"
                                    placeholder="Type at least 2 letters, e.g. dal, apple, pizza..."
                                    autocomplete="off"
                                    class="w-full border border-gray-300 rounded-md pl-9 pr-10 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-green-500"
                                    :class="{ 'border-green-500': selectedItem }"
                                />
                                <span v-if="searching" class="absolute right-3 text-gray-400">
                                    <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                    </svg>
                                </span>
                                <button
                                    v-else-if="searchQuery"
                                    type="button"
                                    @mousedown.prevent="clearSelection"
                                    class="absolute right-3 text-gray-400 hover:text-gray-600"
                                    title="Clear"
                                >
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </div>

                            <!-- Dropdown Results -->
                            <div
                                v-if="showDropdown && searchResults.length > 0"
                                class="absolute z-20 w-full mt-1 bg-white border border-gray-200 rounded-md shadow-lg max-h-72 overflow-y-auto"
                            >
                                <ul>
                                    <li
                                        v-for="item in searchResults"
                                        :key="item.id"
                                        @mousedown.prevent="selectItem(item)"
                                        class="flex items-center justify-between px-4 py-2.5 hover:bg-green-50 cursor-pointer border-b border-gray-50 last:border-0"
                                    >
                                        <span class="text-sm font-medium text-gray-800">{{ item.name }}</span>
                                        <span class="text-xs text-gray-400 ml-2 whitespace-nowrap">
                                            {{ item.calories }} kcal / {{ item.unit }}
                                        </span>
                                    </li>
                                </ul>
                            </div>

                            <!-- No results -->
                            <div
                                v-if="showDropdown && searchResults.length === 0 && !searching && searchQuery.length >= 2"
                                class="absolute z-20 w-full mt-1 bg-white border border-gray-200 rounded-md shadow-lg px-4 py-3 text-sm text-gray-400"
                            >
                                No results for "{{ searchQuery }}"
                            </div>
                        </div>

                        <p v-if="errors.food_item_id" class="text-red-500 text-xs mt-1">{{ errors.food_item_id[0] }}</p>
                    </div>

                    <!-- Selected Item Preview -->
                    <div v-if="selectedItem" class="bg-green-50 border border-green-200 rounded-md px-4 py-3 flex items-center justify-between">
                        <div>
                            <p class="font-medium text-gray-800 text-sm">{{ selectedItem.name }}</p>
                            <p class="text-xs text-gray-500 mt-0.5">{{ selectedItem.calories }} kcal per {{ selectedItem.unit }}</p>
                        </div>
                        <button type="button" @click="clearSelection" class="text-gray-400 hover:text-gray-600 ml-3">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <!-- Quantity & Spend -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Quantity</label>
                            <input
                                type="number"
                                v-model="form.quantity"
                                min="1"
                                max="20"
                                class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500"
                            />
                            <p v-if="errors.quantity" class="text-red-500 text-xs mt-1">{{ errors.quantity[0] }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Amount Spent (₹)</label>
                            <input
                                type="number"
                                v-model="form.amount_spent"
                                min="0"
                                step="0.5"
                                placeholder="0.00"
                                class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500"
                            />
                            <p v-if="errors.amount_spent" class="text-red-500 text-xs mt-1">{{ errors.amount_spent[0] }}</p>
                        </div>
                    </div>

                    <!-- Estimated Calories -->
                    <div v-if="estimatedCalories !== null" class="text-sm text-gray-500">
                        Estimated calories: <strong class="text-gray-800">{{ estimatedCalories }} kcal</strong>
                    </div>

                    <!-- Notes -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Notes (optional)</label>
                        <textarea
                            v-model="form.notes"
                            rows="2"
                            placeholder="Any notes about this meal..."
                            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500"
                        ></textarea>
                    </div>

                    <!-- Submit -->
                    <button
                        @click="submit"
                        :disabled="submitting || !form.food_item_id"
                        class="w-full bg-green-600 text-white py-2.5 rounded-md font-medium hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed transition"
                    >
                        {{ submitting ? 'Saving...' : 'Add to Log' }}
                    </button>

                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
