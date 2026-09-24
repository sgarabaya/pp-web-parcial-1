function loadSalesOverview(data) {
  const labels = data.map((row) => row.userName);
  const amountData = data.map((row) => row.totalAmount);
  const countData = data.map((row) => row.salesCount);

  const ctx = document.getElementById("sales-chart").getContext("2d");

  new Chart(ctx, {
    type: "pie",
    data: {
      labels: labels,
      datasets: [
        {
          label: "Ingresos Totales",
          data: amountData,
          backgroundColor: [
            "#FF6384",
            "#36A2EB",
            "#FFCE56",
            "#4BC0C0",
            "#9966FF",
            "#FF9F40",
          ],
        },
        {
          label: "Cantidad de Ventas",
          data: countData,
          backgroundColor: [
            "#FF6384",
            "#36A2EB",
            "#FFCE56",
            "#4BC0C0",
            "#9966FF",
            "#FF9F40",
          ],
        },
      ],
    },
    options: {
      responsive: true,
      plugins: {
        tooltip: {
          callbacks: {
            label: function (context) {
              const label = context.dataset.label || "";
              const val = context.raw;
              if (context.datasetIndex === 0) {
                return `${label}: $${Number(val).toFixed(2)}`;
              }
              return `${label}: ${val}`;
            },
          },
        },
      },
    },
  });
}
