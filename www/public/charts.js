const groupBy = (data, key) =>
  data.reduce((acc, row) => {
    (acc[row[key]] = acc[row[key]] || []).push(row);
    return acc;
  }, {});

const getRandomColor = () =>
  `#${Math.floor(Math.random() * 16777215)
    .toString(16)
    .padStart(6, "0")}`;

const $ = (id) => document.getElementById(id);

function salesTrend(target, monthlyData, trendLabels) {
  new Chart(target, {
    type: "bar",
    data: {
      labels: trendLabels,
      datasets: [
        {
          label: "Ganancia Total",
          data: trendLabels.map((m) =>
            monthlyData[m].reduce((sum, r) => sum + r.paidAmount, 0),
          ),
          backgroundColor: "rgb(100,100,255)",
        },
      ],
    },
  });
}

function mostSoldVehicles(target, vehicleData, vehicleLabels) {
  new Chart(target, {
    type: "bar",
    data: {
      labels: vehicleLabels,
      datasets: [
        {
          label: "Unidades vendidas",
          data: vehicleLabels.map((v) => vehicleData[v].length),
          backgroundColor: vehicleLabels.map(() => getRandomColor()),
        },
      ],
    },
  });
}

function salesPerEmployee(target, employees, trendLabels, monthlyData) {
  new Chart(target, {
    type: "bar",
    data: {
      labels: trendLabels,
      datasets: employees.map((emp) => ({
        label: emp,
        data: trendLabels.map((month) =>
          (monthlyData[month] || [])
            .filter((r) => r.employee === emp)
            .reduce((sum, r) => sum + r.paidAmount, 0),
        ),
        backgroundColor: trendLabels.map(() => getRandomColor()),
      })),
    },
    options: { scales: { x: { stacked: true }, y: { stacked: true } } },
  });
}

function stockVsSales(target, vehicleLabels, vehicleData) {
  new Chart(target, {
    type: "bar",
    data: {
      labels: vehicleLabels,
      datasets: [
        {
          label: "Unidades Vendidas",
          data: vehicleLabels.map((v) => vehicleData[v].length),
          yAxisID: "y",
          backgroundColor: "rgb(100,100,255)",
        },
        {
          label: "Inventario Actual",
          data: vehicleLabels.map((v) => vehicleData[v][0].vehicleStock),
          yAxisID: "y1",
          backgroundColor: "rgb(255,100,100)",
        },
      ],
    },
    options: {
      scales: {
        y: { type: "linear", position: "left" },
        y1: {
          type: "linear",
          position: "right",
          grid: { drawOnChartArea: false },
        },
      },
    },
  });
}

function createCharts(data) {
  //default colors & style
  Chart.defaults.color = "#E0E0E0";
  Chart.defaults.borderColor = "rgba(255, 255, 255, 0.15)";

  const monthlyData = groupBy(data, "created");
  const trendLabels = Object.keys(monthlyData).sort();
  salesTrend($("trend-chart"), monthlyData, trendLabels);

  const vehicleData = groupBy(data, "vehicle");
  const vehicleLabels = Object.keys(vehicleData);
  mostSoldVehicles($("vehicles-chart"), vehicleData, vehicleLabels);

  const employees = [...new Set(data.map((r) => r.employee))];
  salesPerEmployee($("employee-chart"), employees, trendLabels, monthlyData);

  stockVsSales($("stock-chart"), vehicleLabels, vehicleData);
}
