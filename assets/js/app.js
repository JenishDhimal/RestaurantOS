document.addEventListener("DOMContentLoaded", function () {
  const sidebar = document.getElementById("sidebar");
  const overlay = document.getElementById("sidebarOverlay");
  const toggleBtn = document.getElementById("sidebarToggle");

  function openSidebar() {
    sidebar && sidebar.classList.add("sidebar-open");
    overlay && overlay.classList.add("active");
  }
  function closeSidebar() {
    sidebar && sidebar.classList.remove("sidebar-open");
    overlay && overlay.classList.remove("active");
  }

  toggleBtn && toggleBtn.addEventListener("click", function () {
    sidebar && sidebar.classList.contains("sidebar-open") ? closeSidebar() : openSidebar();
  });
  overlay && overlay.addEventListener("click", closeSidebar);

  // Close sidebar on nav link click (mobile)
  sidebar && sidebar.querySelectorAll("a").forEach(function (link) {
    link.addEventListener("click", function () {
      if (window.innerWidth < 992) closeSidebar();
    });
  });


  const revenueCanvas = document.getElementById("revenueChart");
  if (revenueCanvas && typeof revenueLabels !== "undefined") {
    const ctx = revenueCanvas.getContext("2d");
    const gradient = ctx.createLinearGradient(0, 0, 0, 160);
    gradient.addColorStop(0, "rgba(232,93,4,0.18)");
    gradient.addColorStop(1, "rgba(232,93,4,0.01)");
    new Chart(revenueCanvas, {
      type: "line",
      data: {
        labels: revenueLabels,
        datasets: [
          {
            label: "Revenue ($)",
            data: revenueValues,
            backgroundColor: gradient,
            borderColor: "#E85D04",
            borderWidth: 2,
            fill: true,
            tension: 0.4,
            pointRadius: revenueLabels.length > 30 ? 0 : 3,
            pointHoverRadius: 5,
            pointBackgroundColor: "#E85D04",
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
          x: {
            grid: { display: false },
            ticks: { display: false },
            border: { display: false },
          },
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
