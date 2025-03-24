<template>
    <div class="p-6 bg-white rounded-lg shadow">
      <h2 class="text-xl font-bold mb-4">Attendance History</h2>
  
      <button @click="exportPdf" class="bg-blue-500 text-white px-4 py-2 rounded">
        Export as PDF
      </button>
  
      <table class="w-full mt-4 border">
        <thead>
          <tr class="bg-gray-200">
            <th class="p-2 border">ID</th>
            <th class="p-2 border">Employee Name</th>
            <th class="p-2 border">Date & Time</th>
            <th class="p-2 border">Status</th>
            <th class="p-2 border">Type</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="attendance in attendances" :key="attendance.id" class="text-center">
            <td class="p-2 border">{{ attendance.id }}</td>
            <td class="p-2 border">{{ attendance.employee ? `${attendance.employee.first_name} ${attendance.employee.last_name}` : 'N/A' }}</td>
            <td class="p-2 border">{{ attendance.created_at }}</td>
            <td class="p-2 border">{{ attendance.status }}</td>
            <td class="p-2 border">{{ attendance.type }}</td>
          </tr>
        </tbody>
      </table>
    </div>
  </template>
  
  <script>
  import { ref, onMounted } from "vue";
  import { router } from "@inertiajs/vue3";
  import jsPDF from "jspdf";
  import "jspdf-autotable";
  
  export default {
    setup() {
      const attendances = ref([]);
  
      const fetchAttendances = () => {
        router.get("/attendance/export", {}, {
          preserveState: true,
          onSuccess: (page) => {
            attendances.value = page.props.attendances;
          },
        });
      };
  
      const exportPdf = () => {
        const doc = new jsPDF();
        doc.text("Attendance Report", 10, 10);
  
        const rows = attendances.value.map(attendance => [
          attendance.id,
          attendance.employee ? `${attendance.employee.first_name} ${attendance.employee.last_name}` : "N/A",
          attendance.created_at,
          attendance.status,
          attendance.type
        ]);
  
        doc.autoTable({
          head: [["ID", "Employee Name", "Date & Time", "Status", "Type"]],
          body: rows,
        });
  
        doc.save("attendance_report.pdf");
      };
  
      onMounted(fetchAttendances);
  
      return { attendances, exportPdf };
    },
  };
  </script>
  