// import { Chart } from "@/components/ui/chart"
/**
 * Dashboard JavaScript - SISPROMIN
 * Sistema de Producción Minera
 */

// Variables globales
let chartTendencia;
let chartDistribucion;
let chartProduccionHoy;
let dashboardData = {};
let $; // Declare the $ variable

// Configuración de colores para minería
const coloresDashboard = {
  mina: "#8b4513",
  planta: "#228b22",
  amalgamacion: "#ff8c00",
  flotacion: "#4169e1",
  primary: "#1571b0",
  success: "#20c997",
  warning: "#ffc107",
  danger: "#dc3545",
  info: "#17a2b8",
};

// Import jQuery
$ = window.jQuery;

// Inicialización cuando el DOM está listo
$(document).ready(() => {
  console.log("Inicializando Dashboard SISPROMIN...");

  // Cargar datos iniciales
  cargarDatosDashboard();

  // Configurar eventos
  configurarEventos();

  // Actualizar cada 5 minutos
  setInterval(cargarDatosDashboard, 300000);

  // Actualizar reloj
  actualizarReloj();
  setInterval(actualizarReloj, 1000);
});

/**
 * Configura todos los eventos del dashboard
 */
function configurarEventos() {
  // Botón actualizar
  $("#btn-actualizar-dashboard").on("click", function () {
    $(this).addClass("loading");
    cargarDatosDashboard().finally(() => {
      $(this).removeClass("loading");
    });
  });

  // Botones de navegación rápida
  $(".quick-nav-btn").on("click", function () {
    const modulo = $(this).data("modulo");
    if (modulo) {
      window.location.href = modulo;
    }
  });

  // Tarjetas métricas clickeables
  $(".metric-card").on("click", function () {
    $(this).addClass("clicked");
    setTimeout(() => {
      $(this).removeClass("clicked");
    }, 200);
  });
}

/**
 * Carga todos los datos del dashboard
 */
async function cargarDatosDashboard() {
  try {
    console.log("Cargando datos del dashboard...");

    // Mostrar indicadores de carga
    mostrarCargando();

    const response = await fetch("api/dashboard_data.php", {
      method: "GET",
      headers: {
        "Content-Type": "application/json",
      },
    });

    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }

    const data = await response.json();

    if (data.success) {
      dashboardData = data.data;

      // Actualizar métricas principales
      actualizarMetricasPrincipales(dashboardData.metricas_principales);

      // Actualizar producción de hoy
      actualizarProduccionHoy(dashboardData.produccion_hoy);

      // Crear gráficas
      crearGraficaTendenciaSemanal(dashboardData.tendencia_semanal);
      crearGraficaDistribucionProcesos(dashboardData.distribucion_procesos);
      crearGraficaProduccionHoy(dashboardData.produccion_hoy);

      // Actualizar alertas
      actualizarAlertas(dashboardData.alertas);

      // Actualizar actividad reciente
      actualizarActividadReciente(dashboardData.actividad_reciente);

      // Actualizar KPIs
      actualizarKPIsOperacionales(dashboardData.kpis_operacionales);

      console.log("Dashboard cargado exitosamente");
    } else {
      throw new Error(data.message || "Error al cargar datos");
    }
  } catch (error) {
    console.error("Error al cargar dashboard:", error);
    mostrarError("Error al cargar los datos del dashboard");
  } finally {
    ocultarCargando();
  }
}

/**
 * Actualiza las métricas principales
 */
function actualizarMetricasPrincipales(metricas) {
  // Producción hoy
  animarNumero("#produccion-hoy", metricas.produccion_hoy, 2000, 1);

  // Variación vs ayer
  const variacion = metricas.variacion_produccion;
  const variacionElement = $("#variacion-produccion");
  variacionElement.text(`${variacion >= 0 ? "+" : ""}${variacion.toFixed(1)}%`);
  variacionElement.removeClass("positive negative neutral");

  if (variacion > 0) {
    variacionElement.addClass("positive");
  } else if (variacion < 0) {
    variacionElement.addClass("negative");
  } else {
    variacionElement.addClass("neutral");
  }

  // Registros hoy
  animarNumero("#registros-hoy", metricas.registros_hoy);

  // Ley promedio
  animarNumero("#ley-promedio", metricas.ley_promedio, 2000, 2);

  // Registros incompletos
  animarNumero("#registros-incompletos", metricas.registros_incompletos);

  // Turnos activos
  animarNumero("#turnos-activos", metricas.turnos_activos);

  // Eficiencia operacional
  animarNumero(
    "#eficiencia-operacional",
    metricas.eficiencia_operacional,
    2000,
    1
  );

  // Actualizar barra de progreso de eficiencia
  const eficienciaBar = $("#eficiencia-bar");
  eficienciaBar.css("width", `${metricas.eficiencia_operacional}%`);

  // Cambiar color según eficiencia
  eficienciaBar.removeClass("bg-success bg-warning bg-danger");
  if (metricas.eficiencia_operacional >= 85) {
    eficienciaBar.addClass("bg-success");
  } else if (metricas.eficiencia_operacional >= 70) {
    eficienciaBar.addClass("bg-warning");
  } else {
    eficienciaBar.addClass("bg-danger");
  }
}

/**
 * Actualiza la sección de producción de hoy
 */
function actualizarProduccionHoy(produccionHoy) {
  const container = $("#produccion-hoy-detalle");
  container.empty();

  Object.keys(produccionHoy).forEach((proceso) => {
    const data = produccionHoy[proceso];
    const porcentajeMeta =
      data.meta_diaria > 0 ? (data.produccion / data.meta_diaria) * 100 : 0;
    const colorProceso = coloresDashboard[proceso] || coloresDashboard.primary;
    const estadoClass =
      porcentajeMeta >= 100
        ? "success"
        : porcentajeMeta >= 80
        ? "warning"
        : "danger";

    const procesoCard = `
      <div class="proceso-card">
        <div class="proceso-info">
          <div class="proceso-icon" style="background: ${colorProceso}">
            <i class="bi bi-${getIconoProceso(proceso)}"></i>
          </div>
          <div class="proceso-details">
            <h4>${proceso.charAt(0).toUpperCase() + proceso.slice(1)}</h4>
            <span class="proceso-registros">${data.registros} registros</span>
          </div>
        </div>
        <div class="proceso-metrics">
          <div class="proceso-metric">
            <span class="proceso-metric-value">${data.produccion.toFixed(
              1
            )}</span>
            <span class="proceso-metric-label">Prod. (t)</span>
          </div>
          <div class="proceso-metric">
            <span class="proceso-metric-value">${data.meta_diaria.toFixed(
              0
            )}</span>
            <span class="proceso-metric-label">Meta (t)</span>
          </div>
          <div class="proceso-metric">
            <span class="proceso-metric-value">${porcentajeMeta.toFixed(
              0
            )}%</span>
            <span class="proceso-metric-label">Cumplim.</span>
          </div>
        </div>
        <div class="proceso-estado">
          <span class="badge bg-${estadoClass}">${data.estado}</span>
        </div>
      </div>
    `;

    container.append(procesoCard);
  });
}

/**
 * Crea la gráfica de tendencia semanal
 */
function crearGraficaTendenciaSemanal(data) {
  const ctx = document
    .getElementById("chart-tendencia-semanal")
    .getContext("2d");

  if (chartTendencia) {
    chartTendencia.destroy();
  }

  chartTendencia = new Chart(ctx, {
    type: "line",
    data: {
      labels: data.fechas,
      datasets: [
        {
          label: "Producción Diaria",
          data: data.produccion,
          borderColor: coloresDashboard.primary,
          backgroundColor: coloresDashboard.primary + "20",
          tension: 0.4,
          fill: true,
          pointBackgroundColor: coloresDashboard.primary,
          pointBorderColor: "#fff",
          pointBorderWidth: 2,
          pointRadius: 5,
        },
        {
          label: "Meta Diaria",
          data: Array(data.fechas.length).fill(data.meta_diaria),
          borderColor: coloresDashboard.danger,
          backgroundColor: "transparent",
          borderDash: [5, 5],
          pointRadius: 0,
          tension: 0,
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          position: "top",
          labels: {
            usePointStyle: true,
            padding: 20,
            font: {
              size: 12,
              weight: "600",
            },
          },
        },
        tooltip: {
          callbacks: {
            label: (context) => {
              return `${context.dataset.label}: ${context.parsed.y.toFixed(
                1
              )} t`;
            },
          },
        },
      },
      scales: {
        x: {
          grid: {
            display: false,
          },
          ticks: {
            font: {
              size: 11,
              weight: "500",
            },
          },
        },
        y: {
          beginAtZero: true,
          grid: {
            color: "rgba(0,0,0,0.1)",
          },
          ticks: {
            font: {
              size: 11,
              weight: "500",
            },
            callback: (value) => value.toFixed(0) + " t",
          },
        },
      },
      animation: {
        duration: 2000,
        easing: "easeOutQuart",
      },
    },
  });
}

/**
 * Crea la gráfica de distribución de procesos
 */
function crearGraficaDistribucionProcesos(data) {
  const ctx = document
    .getElementById("chart-distribucion-procesos")
    .getContext("2d");

  if (chartDistribucion) {
    chartDistribucion.destroy();
  }

  const labels = data.map((item) => item.proceso);
  const valores = data.map((item) => item.produccion);
  const colores = data.map((item) => item.color);

  chartDistribucion = new Chart(ctx, {
    type: "doughnut",
    data: {
      labels: labels,
      datasets: [
        {
          data: valores,
          backgroundColor: colores,
          borderWidth: 3,
          borderColor: "#fff",
          hoverBorderWidth: 4,
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          position: "bottom",
          labels: {
            padding: 20,
            usePointStyle: true,
            font: {
              size: 12,
              weight: "600",
            },
          },
        },
        tooltip: {
          callbacks: {
            label: (context) => {
              const total = context.dataset.data.reduce((a, b) => a + b, 0);
              const percentage = Math.round((context.parsed / total) * 100);
              return `${context.label}: ${context.parsed.toFixed(
                1
              )} t (${percentage}%)`;
            },
          },
        },
      },
      cutout: "60%",
      animation: {
        animateRotate: true,
        duration: 2000,
      },
    },
  });
}

/**
 * Crea la gráfica de producción de hoy (barras)
 */
function crearGraficaProduccionHoy(data) {
  const ctx = document.getElementById("chart-produccion-hoy").getContext("2d");

  if (chartProduccionHoy) {
    chartProduccionHoy.destroy();
  }

  const procesos = Object.keys(data);
  const produccion = procesos.map((p) => data[p].produccion);
  const metas = procesos.map((p) => data[p].meta_diaria);
  const colores = procesos.map(
    (p) => coloresDashboard[p] || coloresDashboard.primary
  );

  chartProduccionHoy = new Chart(ctx, {
    type: "bar",
    data: {
      labels: procesos.map((p) => p.charAt(0).toUpperCase() + p.slice(1)),
      datasets: [
        {
          label: "Producción Actual",
          data: produccion,
          backgroundColor: colores,
          borderRadius: 6,
          borderSkipped: false,
        },
        {
          label: "Meta Diaria",
          data: metas,
          backgroundColor: "rgba(220, 53, 69, 0.3)",
          borderColor: coloresDashboard.danger,
          borderWidth: 2,
          borderRadius: 6,
          borderSkipped: false,
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          position: "top",
          labels: {
            usePointStyle: true,
            padding: 20,
            font: {
              size: 12,
              weight: "600",
            },
          },
        },
        tooltip: {
          callbacks: {
            label: (context) => {
              return `${context.dataset.label}: ${context.parsed.y.toFixed(
                1
              )} t`;
            },
          },
        },
      },
      scales: {
        x: {
          grid: {
            display: false,
          },
          ticks: {
            font: {
              size: 11,
              weight: "500",
            },
          },
        },
        y: {
          beginAtZero: true,
          grid: {
            color: "rgba(0,0,0,0.1)",
          },
          ticks: {
            font: {
              size: 11,
              weight: "500",
            },
            callback: (value) => value.toFixed(0) + " t",
          },
        },
      },
      animation: {
        duration: 2000,
        easing: "easeOutQuart",
      },
    },
  });
}

/**
 * Actualiza las alertas del sistema
 */
function actualizarAlertas(alertas) {
  const container = $("#alertas-container");
  container.empty();

  alertas.forEach((alerta) => {
    const alertaElement = `
            <div class="alert alert-${alerta.tipo} alert-dismissible fade show" role="alert">
                <i class="bi bi-${alerta.icono} me-2"></i>
                <strong>${alerta.titulo}:</strong> ${alerta.mensaje}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
    container.append(alertaElement);
  });
}

/**
 * Actualiza la actividad reciente
 */
function actualizarActividadReciente(actividades) {
  const container = $("#actividad-reciente");
  container.empty();

  if (actividades.length === 0) {
    container.append(
      '<p class="text-muted text-center">No hay actividad reciente</p>'
    );
    return;
  }

  actividades.forEach((actividad) => {
    const actividadElement = `
            <div class="actividad-item">
                <div class="actividad-icon">
                    <i class="bi bi-${getIconoProceso(
                      actividad.proceso.toLowerCase()
                    )}"></i>
                </div>
                <div class="actividad-content">
                    <div class="actividad-titulo">${actividad.proceso} - ${
      actividad.accion
    }</div>
                    <div class="actividad-detalle">Código: ${
                      actividad.codigo
                    }</div>
                    <div class="actividad-tiempo">${
                      actividad.tiempo_relativo
                    }</div>
                </div>
            </div>
        `;
    container.append(actividadElement);
  });
}

/**
 * Actualiza los KPIs operacionales
 */
function actualizarKPIsOperacionales(kpis) {
  // Disponibilidad de equipos
  animarNumero("#kpi-disponibilidad", kpis.disponibilidad_equipos, 2000, 1);
  actualizarBarraProgreso("#disponibilidad-bar", kpis.disponibilidad_equipos);

  // Tiempo promedio de proceso
  animarNumero("#kpi-tiempo-proceso", kpis.tiempo_promedio_proceso, 2000, 1);

  // Calidad de ley
  animarNumero("#kpi-calidad-ley", kpis.calidad_ley, 2000, 1);
  actualizarBarraProgreso("#calidad-bar", kpis.calidad_ley);

  // Cumplimiento de metas
  animarNumero("#kpi-cumplimiento", kpis.cumplimiento_metas, 2000, 1);
  actualizarBarraProgreso("#cumplimiento-bar", kpis.cumplimiento_metas);
}

/**
 * Funciones de utilidad
 */
function getIconoProceso(proceso) {
  const iconos = {
    mina: "minecart",
    planta: "gear-wide-connected",
    amalgamacion: "droplet-half",
    flotacion: "water",
  };
  return iconos[proceso] || "circle";
}

function animarNumero(selector, valorFinal, duracion = 2000, decimales = 0) {
  const elemento = $(selector);
  const valorInicial = 0;
  const incremento = valorFinal / (duracion / 16);
  let valorActual = valorInicial;

  const timer = setInterval(() => {
    valorActual += incremento;
    if (valorActual >= valorFinal) {
      valorActual = valorFinal;
      clearInterval(timer);
    }
    elemento.text(
      valorActual.toFixed(decimales).replace(/\B(?=(\d{3})+(?!\d))/g, ",")
    );
  }, 16);
}

function actualizarBarraProgreso(selector, porcentaje) {
  const barra = $(selector);
  barra.css("width", `${Math.min(100, porcentaje)}%`);

  // Cambiar color según porcentaje
  barra.removeClass("bg-success bg-warning bg-danger");
  if (porcentaje >= 85) {
    barra.addClass("bg-success");
  } else if (porcentaje >= 70) {
    barra.addClass("bg-warning");
  } else {
    barra.addClass("bg-danger");
  }
}

function mostrarCargando() {
  $(".metric-value").html(
    '<div class="spinner-border spinner-border-sm"></div>'
  );
  $(".chart-container canvas").hide();
  $(".chart-container").append(
    '<div class="text-center p-4 loading-chart"><div class="spinner-border"></div><br><small>Cargando...</small></div>'
  );
}

function ocultarCargando() {
  $(".loading-chart").remove();
  $(".chart-container canvas").show();
}

function mostrarError(mensaje) {
  console.error(mensaje);
  if (typeof window.showErrorToast === "function") {
    window.showErrorToast(mensaje);
  }
}

function actualizarReloj() {
  const ahora = new Date();
  const tiempo = ahora.toLocaleTimeString("es-ES", {
    hour: "2-digit",
    minute: "2-digit",
    second: "2-digit",
  });
  const fecha = ahora.toLocaleDateString("es-ES", {
    weekday: "long",
    year: "numeric",
    month: "long",
    day: "numeric",
  });

  $("#reloj-tiempo").text(tiempo);
  $("#reloj-fecha").text(fecha);
}

// Efectos adicionales
$(document)
  .on("mouseenter", ".metric-card", function () {
    $(this).addClass("hover-effect");
  })
  .on("mouseleave", ".metric-card", function () {
    $(this).removeClass("hover-effect");
  });

// Auto-refresh visual indicator
let refreshCounter = 300; // 5 minutos
setInterval(() => {
  refreshCounter--;
  if (refreshCounter <= 0) {
    refreshCounter = 300;
  }
  $("#next-refresh").text(
    `Próxima actualización en ${Math.floor(refreshCounter / 60)}:${(
      refreshCounter % 60
    )
      .toString()
      .padStart(2, "0")}`
  );
}, 1000);
