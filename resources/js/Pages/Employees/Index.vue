<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import dayjs from 'dayjs';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { TrashIcon, PencilIcon, DocumentMagnifyingGlassIcon, FunnelIcon, XMarkIcon } from '@heroicons/vue/24/outline';
import debounce from 'lodash/debounce';

const props = defineProps({
    employees: Object,
    error: String,
    loading: Boolean,
    filters: {
        type: Object,
        default: () => ({
            search: '',
            status: '',
            position: '',
            country: '',
            city: '',
            dateFrom: '',
            dateTo: ''
        })
    },
    positions: {
        type: Array,
        default: () => []
    },
    countries: {
        type: Array,
        default: () => []
    },
    cities: {
        type: Array,
        default: () => []
    }
});

// Search and filter states
const searchQuery = ref(props.filters.search || '');
const statusFilter = ref(props.filters.status || '');
const positionFilter = ref(props.filters.position || '');
const countryFilter = ref(props.filters.country || '');
const cityFilter = ref(props.filters.city || '');
const dateRangeStart = ref(props.filters.dateFrom || '');
const dateRangeEnd = ref(props.filters.dateTo || '');
const showAdvancedFilters = ref(false);

const editEmployee = (employee) => router.get(route('employee.edit', {id: employee.id}));
const addEmployee = () => router.get(route('employeeShowForm'));
const viewEmployee = (employee) => router.get(route('employee.view', {id: employee.id}));

const deleteEmployee = (id) => {
    if (confirm(`Delete employee #${id}?`)) {
        router.delete(route('employee.destroy', id), {
            onSuccess: () => console.log(`Employee #${id} deleted successfully`),
            onError: (error) => console.error(`Failed to delete:`, error)
        });
    }
};

// Global search function that sends request to server
const performSearch = debounce(() => {
    router.get(
        route('employees.index'), 
        {
            search: searchQuery.value,
            status: statusFilter.value,
            position: positionFilter.value,
            country: countryFilter.value,
            city: cityFilter.value, 
            dateFrom: dateRangeStart.value,
            dateTo: dateRangeEnd.value
        },
        {
            preserveState: true,
            replace: true,
            only: ['employees']
        }
    );
}, 500);

// Watch for changes in search filters
watch([searchQuery, statusFilter, positionFilter, countryFilter, cityFilter, dateRangeStart, dateRangeEnd], 
    () => {
        performSearch();
    }
);

const resetFilters = () => {
    searchQuery.value = '';
    statusFilter.value = '';
    positionFilter.value = '';
    countryFilter.value = '';
    cityFilter.value = '';
    dateRangeStart.value = '';
    dateRangeEnd.value = '';
    
    performSearch();
};

const goToPage = (url) => {
    if(url) {
        router.get(url);
    }
};
</script>

<template>
    <Head title="Employees" />
    
    <AuthenticatedLayout>
        <div class="py-8 max-w-7xl mx-auto sm:px-4">
            <div class="p-4 bg-white shadow-md rounded-lg">
                <h2 class="text-lg font-semibold mb-3">Employees List</h2>
                
                <!-- Search and basic filters -->
                <div class="flex flex-wrap gap-2 justify-between mb-4">
                    <div class="flex flex-wrap gap-2 items-center">
                        <div class="relative">
                            <input 
                                v-model="searchQuery" 
                                type="text" 
                                placeholder="Search globally..." 
                                class="border p-2 rounded w-64 pr-8 text-sm focus:border-blue-500 focus:ring-blue-500"
                            >
                            <button 
                                v-if="searchQuery" 
                                @click="searchQuery = ''" 
                                class="absolute right-2 top-2 text-gray-400 hover:text-gray-600"
                            >
                                <XMarkIcon class="w-4 h-4" />
                            </button>
                        </div>
                        
                        <select v-model="statusFilter" class="border p-2 rounded text-sm">
                            <option value="">All Status</option>
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                        </select>
                        
                        <button 
                            @click="showAdvancedFilters = !showAdvancedFilters" 
                            class="flex items-center gap-1 text-blue-600 text-sm py-1 px-2 border border-blue-600 rounded hover:bg-blue-50"
                        >
                            <FunnelIcon class="w-4 h-4" />
                            {{ showAdvancedFilters ? 'Hide' : 'Advanced' }} Filters
                        </button>
                    </div>
                    <div class="flex gap-2">
                        <SecondaryButton @click="router.reload()">Refresh</SecondaryButton>
                        <PrimaryButton @click="addEmployee">+ Add</PrimaryButton>
                    </div>
                </div>
                
                <!-- Advanced filters section -->
                <div v-if="showAdvancedFilters" class="mb-4 p-3 bg-gray-50 rounded-lg">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">Position</label>
                            <select v-model="positionFilter" class="w-full border rounded p-2 text-sm">
                                <option value="">All Positions</option>
                                <option v-for="position in positions" :key="position.id" :value="position.id">
                                    {{ position.name }}
                                </option>
                            </select>
                        </div>
                        
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">Country</label>
                            <select v-model="countryFilter" class="w-full border rounded p-2 text-sm">
                                <option value="">All Countries</option>
                                <option v-for="country in countries" :key="country" :value="country">
                                    {{ country }}
                                </option>
                            </select>
                        </div>
                        
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">City</label>
                            <select 
                                v-model="cityFilter" 
                                class="w-full border rounded p-2 text-sm"
                                :disabled="!countryFilter"
                            >
                                <option value="">All Cities</option>
                                <option v-for="city in cities" :key="city" :value="city">
                                    {{ city }}
                                </option>
                            </select>
                        </div>
                        
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">Joined From</label>
                            <input 
                                v-model="dateRangeStart" 
                                type="date" 
                                class="w-full border rounded p-2 text-sm"
                            >
                        </div>
                        
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">Joined To</label>
                            <input 
                                v-model="dateRangeEnd" 
                                type="date" 
                                class="w-full border rounded p-2 text-sm"
                            >
                        </div>
                        
                        <div class="flex items-end">
                            <button 
                                @click="resetFilters" 
                                class="px-3 py-2 border border-gray-300 rounded text-sm text-gray-700 hover:bg-gray-100"
                            >
                                Clear All Filters
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Status messages -->
                <div v-if="error" class="bg-red-100 text-red-700 p-2 rounded mb-3 text-sm">{{ error }}</div>
                <div v-if="loading" class="text-center py-3 text-sm">Loading...</div>
                
                <!-- Results count -->
                <div v-if="!loading" class="mb-2 text-sm text-gray-600">
                    Showing {{ employees.data.length }} {{ employees.data.length === 1 ? 'employee' : 'employees' }}
                    <span v-if="searchQuery || statusFilter || positionFilter || countryFilter || cityFilter || dateRangeStart || dateRangeEnd">
                        with applied filters
                    </span>
                </div>

                <!-- Employees table -->
                <div v-if="!loading && employees.data.length">
                    <table class="w-full border-collapse border text-sm">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="border p-1">#</th>
                                <th class="border p-1">Name</th>
                                <th class="border p-1">Email</th>
                                <th class="border p-1">Country</th>
                                <th class="border p-1">City</th>
                                <th class="border p-1">Position</th>
                                <th class="border p-1">Joined</th>
                                <th class="border p-1">Status</th>
                                <th class="border p-1">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="emp in employees.data" :key="emp.id" class="hover:bg-gray-50 text-center">
                                <td class="border p-1">{{ emp.id }}</td>
                                <td class="border p-1">{{ emp.first_name }} {{ emp.last_name }}</td>
                                <td class="border p-1">{{ emp.email }}</td>
                                <td class="border p-1">{{ emp.country }}</td>
                                <td class="border p-1">{{ emp.city }}</td>
                                <td class="border p-1">{{ emp.position?.name }}</td>
                                <td class="border p-1">{{ dayjs(emp.created_at).format('MMM D, YYYY') }}</td>
                                <td class="border p-1">
                                    <span class="px-2 py-1 rounded text-xs font-medium" 
                                        :class="emp.status === 'Active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'">
                                        {{ emp.status }}
                                    </span>
                                </td>
                                <td class="border p-1">
                                    <div class="flex justify-center space-x-1">
                                        <button @click="editEmployee(emp)" class="text-gray-700 focus:outline-none" title="Edit">
                                            <PencilIcon class="w-4 h-4"/>
                                        </button>
                                        <button @click="viewEmployee(emp)" class="text-gray-700 focus:outline-none" title="View">
                                             <DocumentMagnifyingGlassIcon class="w-4 h-4"/>
                                        </button>
                                        <button @click="deleteEmployee(emp.id)" class="text-gray-700 focus:outline-none" title="Delete">
                                            <TrashIcon class="w-4 h-4"/>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <!-- No results message -->
                <div v-if="!loading && !employees.data.length" class="text-center py-8 text-gray-500">
                    No employees found matching your search criteria.
                </div>

                <!-- Pagination -->
                <div v-if="employees.links.length > 3" class="mt-4 flex justify-center space-x-1 text-sm">
                    <button v-for="link in employees.links" :key="link.label" @click="goToPage(link.url)" :disabled="!link.url"
                        class="px-2 py-1 border rounded" 
                        :class="{'bg-blue-500 text-white': link.active, 'text-gray-700': !link.active}">
                        <span v-html="link.label"></span>
                    </button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>