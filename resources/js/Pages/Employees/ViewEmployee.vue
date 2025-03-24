<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { computed } from 'vue';

const props = defineProps({
    employee: Object
});

const formattedHireDate = computed(() => {
    if (!props.employee.hire_date) return 'N/A';
    return new Date(props.employee.hire_date).toLocaleDateString('en-US', {
        year: 'numeric', 
        month: 'long', 
        day: 'numeric'
    });
});

const formattedBirthdate = computed(() => {
    if (!props.employee.birthdate) return 'N/A';
    return new Date(props.employee.birthdate).toLocaleDateString('en-US', {
        year: 'numeric', 
        month: 'long', 
        day: 'numeric'
    });
});

const fullAddress = computed(() => {
    const parts = [
        props.employee.street_address,
        props.employee.city,
        props.employee.state,
        props.employee.zip_code
    ].filter(Boolean);
    
    return parts.length ? parts.join(', ') : 'No address on file';
});
</script>

<template>
    <Head title="Employee Details" />
    <AuthenticatedLayout>
        <div class="py-5 max-w-7xl mx-auto sm:px-6">
            <div class="bg-white overflow-hidden shadow-sm rounded-lg min-h-96">
                <!-- Simple Header -->
                <div class="px-8 py-5 border-b border-gray-200">
                    <h1 class="text-xl font-semibold text-gray-800 flex items-center">
                        Employee Profile
                    </h1>
                </div>

                <div class="p-8">
                    <div class="flex items-center gap-6 pb-6 border-b border-gray-200">
                        <!-- Profile Image -->
                        <img 
                            class="w-20 h-20 rounded-full object-cover border border-gray-200" 
                            :src="employee.profile_image" 
                            alt="Employee Avatar"
                        />
                        <!-- Name and basic info -->
                        <div>
                            <h2 class="text-2xl font-semibold text-gray-900">
                                {{ employee.first_name }} {{ employee.last_name }}
                            </h2>
                            <p class="text-lg text-gray-600">{{ employee.position?.name || 'No Position Assigned' }}</p>
                            
                            <!-- Status badge -->
                            <span 
                                class="inline-flex items-center px-3 py-1 mt-2 rounded text-sm font-medium"
                                :class="{
                                    'bg-green-100 text-green-800': employee.status === 'Active',
                                    'bg-red-100 text-red-800': employee.status !== 'Active'
                                }"
                            >
                                {{ employee.status }}
                            </span>
                        </div>
                    </div>

                    <!-- Information in a wider grid with more height -->
                    <div class="mt-6 grid grid-cols-1 sm:grid-cols-3 gap-6">
                        <!-- Personal Information -->
                        <div>
                            <h3 class="text-lg font-medium text-gray-800 mb-3">Personal Information</h3>
                            <table class="w-full text-base">
                                <tbody>
                                    <tr>
                                        <td class="py-2 text-gray-500">RFID Tag:</td>
                                        <td class="py-2">{{ employee.rfid_tag || 'Not Assigned' }}</td>
                                    </tr>
                                    <tr>
                                        <td class="py-2 text-gray-500">Birthdate:</td>
                                        <td class="py-2">{{ formattedBirthdate }}</td>
                                    </tr>
                                    <tr>
                                        <td class="py-2 text-gray-500">Gender:</td>
                                        <td class="py-2">{{ employee.gender || 'N/A' }}</td>
                                    </tr>
                                    <tr>
                                        <td class="py-2 text-gray-500">Hired On:</td>
                                        <td class="py-2">{{ formattedHireDate }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Contact Information -->
                        <div>
                            <h3 class="text-lg font-medium text-gray-800 mb-3">Contact Information</h3>
                            <table class="w-full text-base">
                                <tbody>
                                    <tr>
                                        <td class="py-2 text-gray-500">Email:</td>
                                        <td class="py-2">{{ employee.email || 'N/A' }}</td>
                                    </tr>
                                    <tr>
                                        <td class="py-2 text-gray-500">Phone:</td>
                                        <td class="py-2">{{ employee.contact_number || 'N/A' }}</td>
                                    </tr>
                                    <tr>
                                        <td class="py-2 text-gray-500">Emergency:</td>
                                        <td class="py-2">{{ employee.emergency_contact || 'N/A' }}</td>
                                    </tr>
                                    <tr>
                                        <td class="py-2 text-gray-500">Emergency #:</td>
                                        <td class="py-2">{{ employee.emergency_contact_number || 'N/A' }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Address Section -->
                        <div>
                            <h3 class="text-lg font-medium text-gray-800 mb-3">Address</h3>
                            <p class="text-base text-gray-700 leading-relaxed">{{ fullAddress }}</p>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="mt-8 flex justify-end space-x-3 border-t pt-6">
                        <button type="button" class="px-4 py-2 border border-gray-300 rounded text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                            Edit
                        </button>
                        <button type="button" class="px-4 py-2 border border-transparent rounded text-sm font-medium text-white bg-gray-600 hover:bg-gray-700">
                            Print
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>