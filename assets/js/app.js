document.addEventListener("DOMContentLoaded", function () {
  const revenueCanvas = document.getElementById("revenueChart");
  if (revenueCanvas && typeof weekLabels !== "undefined") {
    new Chart(revenueCanvas, {
      type: "bar",
      data: {
        labels: weekLabels,
        datasets: [
          {
            label: "Revenue ($)",
            data: weekValues,
            backgroundColor: "rgba(232, 93, 4, 0.15)",
            borderColor: "#E85D04",
            borderWidth: 2,
            borderRadius: 6,
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { display: false },
          tooltip: {
            callbacks: { label: (ctx) => "$" + ctx.parsed.y.toFixed(2) },
          },
        },
        scales: {
          y: {
            beginAtZero: true,
            grid: { color: "rgba(0,0,0,0.05)" },
            ticks: { callback: (val) => "$" + val, font: { size: 11 } },
          },
          x: { grid: { display: false }, ticks: { font: { size: 11 } } },
        },
      },
    });
  }

  const orderTypeCanvas = document.getElementById("orderTypeChart");
  if (orderTypeCanvas && typeof orderTypeLbls !== "undefined") {
    new Chart(orderTypeCanvas, {
      type: "doughnut",
      data: {
        labels: orderTypeLbls,
        datasets: [
          {
            data: orderTypeVals,
            backgroundColor: ["rgba(59,130,246,0.8)", "rgba(139,92,246,0.8)"],
            borderColor: ["#3b82f6", "#7c3aed"],
            borderWidth: 2,
          },
        ],
      },
      options: {
        responsive: true,
        cutout: "65%",
        plugins: {
          legend: {
            position: "bottom",
            labels: { font: { size: 12 }, padding: 14 },
          },
          tooltip: {
            callbacks: { label: (ctx) => ctx.label + ": " + ctx.parsed },
          },
        },
      },
    });
  }

  const methodCanvas = document.getElementById("payMethodChart");
  if (methodCanvas && typeof methodLbls !== "undefined") {
    new Chart(methodCanvas, {
      type: "doughnut",
      data: {
        labels: methodLbls,
        datasets: [
          {
            data: methodVals,
            backgroundColor: [
              "rgba(16,185,129,0.8)",
              "rgba(59,130,246,0.8)",
              "rgba(139,92,246,0.8)",
            ],
            borderColor: ["#10b981", "#3b82f6", "#7c3aed"],
            borderWidth: 2,
          },
        ],
      },
      options: {
        responsive: true,
        cutout: "65%",
        plugins: {
          legend: {
            position: "bottom",
            labels: { font: { size: 12 }, padding: 14 },
          },
          tooltip: {
            callbacks: { label: (ctx) => ctx.label + ": " + ctx.parsed },
          },
        },
      },
    });
  }

  const topItemsCanvas = document.getElementById("topItemsChart");
  if (topItemsCanvas && typeof topItemLbls !== "undefined") {
    new Chart(topItemsCanvas, {
      type: "bar",
      data: {
        labels: topItemLbls,
        datasets: [
          {
            label: "Qty Sold",
            data: topItemVals,
            backgroundColor: "rgba(232,93,4,0.15)",
            borderColor: "#E85D04",
            borderWidth: 2,
            borderRadius: 4,
          },
        ],
      },
      options: {
        indexAxis: "y",
        responsive: true,
        plugins: {
          legend: { display: false },
          tooltip: { callbacks: { label: (ctx) => ctx.parsed.x + " sold" } },
        },
        scales: {
          x: {
            beginAtZero: true,
            grid: { color: "rgba(0,0,0,0.05)" },
            ticks: { font: { size: 11 }, stepSize: 1 },
          },
          y: { grid: { display: false }, ticks: { font: { size: 11 } } },
        },
      },
    });
  }
});
