// import { Chart } from "@/components/ui/chart"
/**
 * Estadísticas JavaScript - SISPROMIN
 * Sistema de Producción Minera
 */

// Variables globales
let chartProduccionTipo;
let chartTendenciaProduccion;
let chartDistribucionTurnos;
let chartComparacionLeyes;
let chartEficienciaLineas;
let chartProductosQuimicos;
let estadisticasData = {};

// Configuración de colores específicos para minería
const coloresProcesos = {
  mina: "#8b4513",
  planta: "#228b22",
  amalgamacion: "#ff8c00",
  flotacion: "#4169e1",
  primary: "#1571b0",
  success: "#20c997",
  warning: "#ff8c00",
  danger: "#e63946",
  info: "#17a2b8",
  secondary: "#6c757d",
};

// Declaración de la variable $ para evitar errores de lint
const $ = window.$;

// Inicialización cuando el DOM está listo
$(document).ready(() => {
  console.log("Inicializando Estadísticas SISPROMIN...");

  // Cargar datos iniciales
  cargarDatosEstadisticas();

  // Configurar eventos
  configurarEventos();

  // Actualizar timestamp
  actualizarTimestamp();

  // Configurar actualización automática cada 10 minutos
  setInterval(cargarDatosEstadisticas, 600000);
});

/**
 * Configura todos los eventos del módulo
 */
function configurarEventos() {
  // Botón actualizar datos
  $("#btn-actualizar-datos").on("click", function () {
    $(this).addClass("loading");
    cargarDatosEstadisticas().finally(() => {
      $(this).removeClass("loading");
    });
  });

  // Selector de período
  $("#periodo-select").on("change", function () {
    const valor = $(this).val();
    if (valor === "custom") {
      $("#fecha-custom, #fecha-custom-fin").show();
    } else {
      $("#fecha-custom, #fecha-custom-fin").hide();
    }
  });

  // Botón aplicar filtros
  $("#btn-aplicar-filtros").on("click", aplicarFiltrosPeriodo);

  // Botón exportar reporte
  $("#btn-exportar-reporte").on("click", exportarReporteCompleto);

  // Botones de exportación de gráficas
  $("#btn-export-produccion-tipo").on("click", () =>
    exportarGrafica("produccion-tipo")
  );
  $("#btn-export-tendencia").on("click", () => exportarGrafica("tendencia"));
  $("#btn-export-turnos").on("click", () => exportarGrafica("turnos"));
  $("#btn-export-leyes").on("click", () => exportarGrafica("leyes"));
  $("#btn-export-eficiencia").on("click", () => exportarGrafica("eficiencia"));
  $("#btn-export-productos").on("click", () => exportarGrafica("productos"));

  // Botón exportar resumen
  $("#btn-export-resumen").on("click", () => exportarTabla("resumen"));

  // Botón imprimir
  $("#btn-imprimir-reporte").on("click", imprimirReporte);
}

/**
 * Carga todos los datos de estadísticas
 */
async function cargarDatosEstadisticas() {
  try {
    console.log("Cargando datos de estadísticas...");

    // Mostrar indicadores de carga
    mostrarCargando();

    // Obtener parámetros de filtro
    const parametros = obtenerParametrosFiltro();

    // RUTA CORREGIDA: Desde modulos/registros/estadistica/ hacia api/registros/estadistica/
    const response = await fetch(
      "../../../api/registros/estadistica/datos.php",
      {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify(parametros),
      }
    );

    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }

    const data = await response.json();

    if (data.success) {
      estadisticasData = data.data;

      // Actualizar estadísticas principales
      actualizarEstadisticasPrincipales(estadisticasData.estadisticas);

      // Crear gráficas
      crearGraficas(estadisticasData.graficas);

      // Cargar tabla de resumen
      cargarTablaResumen(estadisticasData.resumen);

      // Actualizar KPIs
      actualizarKPIs(estadisticasData.kpis);

      console.log("Datos de estadísticas cargados exitosamente");
    } else {
      throw new Error(data.message || "Error al cargar datos");
    }
  } catch (error) {
    console.error("Error al cargar datos de estadísticas:", error);
    mostrarError("Error al cargar los datos de estadísticas");
  } finally {
    ocultarCargando();
    actualizarTimestamp();
  }
}

/**
 * Obtiene los parámetros de filtro actuales
 */
function obtenerParametrosFiltro() {
  const periodo = $("#periodo-select").val();
  const parametros = { periodo: periodo };

  if (periodo === "custom") {
    parametros.fecha_inicio = $("#fecha-inicio").val();
    parametros.fecha_fin = $("#fecha-fin").val();
  }

  return parametros;
}

/**
 * Aplica los filtros de período
 */
function aplicarFiltrosPeriodo() {
  cargarDatosEstadisticas();
}

/**
 * Actualiza las estadísticas principales
 */
function actualizarEstadisticasPrincipales(stats) {
  // Animar números
  animarNumero("#total-registros", stats.totalRegistros);
  animarNumero("#produccion-total", stats.produccionTotal, 1500, 2);
  animarNumero("#promedio-diario", stats.promedioDiario, 1500, 2);
  animarNumero("#ley-promedio", stats.leyPromedio, 1500, 2);
  animarNumero("#registros-incompletos", stats.registrosIncompletos);
  animarNumero("#turnos-activos", stats.turnosActivos);

  // Actualizar cambios y porcentajes
  $("#registros-change").text(`+${stats.registrosNuevos} este período`);
  $("#produccion-change").text(
    `${stats.crecimientoProduccion.toFixed(1)}% vs período anterior`
  );
  $("#promedio-change").text("Producción diaria");
  $("#ley-change").text("Laboratorio");
  $("#incompletos-change").text("Requieren atención");
  $("#turnos-change").text("En operación");
}

/**
 * Crea todas las gráficas del módulo
 */
function crearGraficas(graficas) {
  // Gráfica 1: Producción por Tipo de Proceso (Dona)
  crearGraficaProduccionTipo(graficas.produccionTipo);

  // Gráfica 2: Tendencia de Producción Diaria (Línea)
  crearGraficaTendenciaProduccion(graficas.tendenciaProduccion);

  // Gráfica 3: Distribución por Turnos (Barras)
  crearGraficaDistribucionTurnos(graficas.distribucionTurnos);

  // Gráfica 4: Comparación Ley Laboratorio vs Inferido (Barras agrupadas)
  crearGraficaComparacionLeyes(graficas.comparacionLeyes);

  // Gráfica 5: Eficiencia por Línea/Frente (Barras horizontales)
  crearGraficaEficienciaLineas(graficas.eficienciaLineas);

  // Gráfica 6: Consumo de Productos Químicos (Radar)
  crearGraficaProductosQuimicos(graficas.productosQuimicos);
}

/**
 * Gráfica 1: Producción por Tipo de Proceso
 */
function crearGraficaProduccionTipo(data) {
  const ctx = document.getElementById("chart-produccion-tipo").getContext("2d");

  if (chartProduccionTipo) {
    chartProduccionTipo.destroy();
  }

  chartProduccionTipo = new Chart(ctx, {
    type: "doughnut",
    data: {
      labels: data.labels,
      datasets: [
        {
          data: data.values,
          backgroundColor: [
            coloresProcesos.mina,
            coloresProcesos.planta,
            coloresProcesos.amalgamacion,
            coloresProcesos.flotacion,
          ],
          borderWidth: 3,
          borderColor: "#fff",
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
            padding: 15,
            usePointStyle: true,
            font: {
              size: 11,
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
                2
              )} t (${percentage}%)`;
            },
          },
        },
      },
      cutout: "60%",
      animation: {
        animateRotate: true,
        duration: 1500,
      },
    },
  });
}

/**
 * Gráfica 2: Tendencia de Producción Diaria
 */
function crearGraficaTendenciaProduccion(data) {
  const ctx = document
    .getElementById("chart-tendencia-produccion")
    .getContext("2d");

  if (chartTendenciaProduccion) {
    chartTendenciaProduccion.destroy();
  }

  chartTendenciaProduccion = new Chart(ctx, {
    type: "line",
    data: {
      labels: data.labels,
      datasets: [
        {
          label: "Mina",
          data: data.mina,
          borderColor: coloresProcesos.mina,
          backgroundColor: coloresProcesos.mina + "20",
          tension: 0.4,
          fill: false,
        },
        {
          label: "Planta",
          data: data.planta,
          borderColor: coloresProcesos.planta,
          backgroundColor: coloresProcesos.planta + "20",
          tension: 0.4,
          fill: false,
        },
        {
          label: "Amalgamación",
          data: data.amalgamacion,
          borderColor: coloresProcesos.amalgamacion,
          backgroundColor: coloresProcesos.amalgamacion + "20",
          tension: 0.4,
          fill: false,
        },
        {
          label: "Flotación",
          data: data.flotacion,
          borderColor: coloresProcesos.flotacion,
          backgroundColor: coloresProcesos.flotacion + "20",
          tension: 0.4,
          fill: false,
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
            padding: 15,
            font: {
              size: 11,
              weight: "600",
            },
          },
        },
        tooltip: {
          mode: "index",
          intersect: false,
          callbacks: {
            label: (context) => {
              return `${context.dataset.label}: ${context.parsed.y.toFixed(
                2
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
              size: 10,
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
              size: 10,
              weight: "500",
            },
            callback: (value) => value.toFixed(1) + " t",
          },
        },
      },
      interaction: {
        mode: "nearest",
        axis: "x",
        intersect: false,
      },
      animation: {
        duration: 1500,
        easing: "easeOutQuart",
      },
    },
  });
}

/**
 * Gráfica 3: Distribución por Turnos
 */
function crearGraficaDistribucionTurnos(data) {
  const ctx = document
    .getElementById("chart-distribucion-turnos")
    .getContext("2d");

  if (chartDistribucionTurnos) {
    chartDistribucionTurnos.destroy();
  }

  chartDistribucionTurnos = new Chart(ctx, {
    type: "bar",
    data: {
      labels: data.labels,
      datasets: [
        {
          label: "Registros",
          data: data.registros,
          backgroundColor: coloresProcesos.primary,
          borderRadius: 4,
          borderSkipped: false,
        },
        {
          label: "Producción (t)",
          data: data.produccion,
          backgroundColor: coloresProcesos.success,
          borderRadius: 4,
          borderSkipped: false,
          yAxisID: "y1",
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
            padding: 15,
            font: {
              size: 11,
              weight: "600",
            },
          },
        },
        tooltip: {
          callbacks: {
            label: (context) => {
              if (context.datasetIndex === 0) {
                return `${context.dataset.label}: ${context.parsed.y} registros`;
              } else {
                return `${context.dataset.label}: ${context.parsed.y.toFixed(
                  2
                )} t`;
              }
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
              size: 10,
              weight: "500",
            },
          },
        },
        y: {
          type: "linear",
          display: true,
          position: "left",
          beginAtZero: true,
          grid: {
            color: "rgba(0,0,0,0.1)",
          },
          ticks: {
            font: {
              size: 10,
              weight: "500",
            },
          },
        },
        y1: {
          type: "linear",
          display: true,
          position: "right",
          beginAtZero: true,
          grid: {
            drawOnChartArea: false,
          },
          ticks: {
            font: {
              size: 10,
              weight: "500",
            },
            callback: (value) => value.toFixed(1) + " t",
          },
        },
      },
      animation: {
        duration: 1500,
        easing: "easeOutQuart",
      },
    },
  });
}

/**
 * Gráfica 4: Comparación Ley Laboratorio vs Inferido
 */
function crearGraficaComparacionLeyes(data) {
  const ctx = document
    .getElementById("chart-comparacion-leyes")
    .getContext("2d");

  if (chartComparacionLeyes) {
    chartComparacionLeyes.destroy();
  }

  chartComparacionLeyes = new Chart(ctx, {
    type: "bar",
    data: {
      labels: data.labels,
      datasets: [
        {
          label: "Ley Laboratorio",
          data: data.laboratorio,
          backgroundColor: coloresProcesos.info,
          borderRadius: 4,
          borderSkipped: false,
        },
        {
          label: "Ley Inferido",
          data: data.inferido,
          backgroundColor: coloresProcesos.warning,
          borderRadius: 4,
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
            padding: 15,
            font: {
              size: 11,
              weight: "600",
            },
          },
        },
        tooltip: {
          callbacks: {
            label: (context) => {
              return `${context.dataset.label}: ${context.parsed.y.toFixed(
                2
              )} g/t`;
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
              size: 10,
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
              size: 10,
              weight: "500",
            },
            callback: (value) => value.toFixed(1) + " g/t",
          },
        },
      },
      animation: {
        duration: 1500,
        easing: "easeOutQuart",
      },
    },
  });
}

/**
 * Gráfica 5: Eficiencia por Línea/Frente
 */
function crearGraficaEficienciaLineas(data) {
  const ctx = document
    .getElementById("chart-eficiencia-lineas")
    .getContext("2d");

  if (chartEficienciaLineas) {
    chartEficienciaLineas.destroy();
  }

  chartEficienciaLineas = new Chart(ctx, {
    type: "bar",
    data: {
      labels: data.labels,
      datasets: [
        {
          label: "Eficiencia (%)",
          data: data.values,
          backgroundColor: data.values.map((value) => {
            if (value >= 90) return coloresProcesos.success;
            if (value >= 70) return coloresProcesos.warning;
            return coloresProcesos.danger;
          }),
          borderRadius: 4,
          borderSkipped: false,
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      indexAxis: "y",
      plugins: {
        legend: {
          display: false,
        },
        tooltip: {
          callbacks: {
            label: (context) => {
              return `Eficiencia: ${context.parsed.x.toFixed(1)}%`;
            },
          },
        },
      },
      scales: {
        x: {
          beginAtZero: true,
          max: 100,
          grid: {
            color: "rgba(0,0,0,0.1)",
          },
          ticks: {
            font: {
              size: 10,
              weight: "500",
            },
            callback: (value) => value + "%",
          },
        },
        y: {
          grid: {
            display: false,
          },
          ticks: {
            font: {
              size: 10,
              weight: "500",
            },
          },
        },
      },
      animation: {
        duration: 1500,
        easing: "easeOutQuart",
      },
    },
  });
}

/**
 * Gráfica 6: Consumo de Productos Químicos
 */
function crearGraficaProductosQuimicos(data) {
  const ctx = document
    .getElementById("chart-productos-quimicos")
    .getContext("2d");

  if (chartProductosQuimicos) {
    chartProductosQuimicos.destroy();
  }

  chartProductosQuimicos = new Chart(ctx, {
    type: "radar",
    data: {
      labels: data.labels,
      datasets: [
        {
          label: "Amalgamación",
          data: data.amalgamacion,
          borderColor: coloresProcesos.amalgamacion,
          backgroundColor: coloresProcesos.amalgamacion + "20",
          pointBackgroundColor: coloresProcesos.amalgamacion,
          pointBorderColor: "#fff",
          pointHoverBackgroundColor: "#fff",
          pointHoverBorderColor: coloresProcesos.amalgamacion,
        },
        {
          label: "Flotación",
          data: data.flotacion,
          borderColor: coloresProcesos.flotacion,
          backgroundColor: coloresProcesos.flotacion + "20",
          pointBackgroundColor: coloresProcesos.flotacion,
          pointBorderColor: "#fff",
          pointHoverBackgroundColor: "#fff",
          pointHoverBorderColor: coloresProcesos.flotacion,
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
            padding: 15,
            font: {
              size: 11,
              weight: "600",
            },
          },
        },
        tooltip: {
          callbacks: {
            label: (context) => {
              return `${context.dataset.label}: ${context.parsed.r.toFixed(
                2
              )} kg`;
            },
          },
        },
      },
      scales: {
        r: {
          beginAtZero: true,
          grid: {
            color: "rgba(0,0,0,0.1)",
          },
          pointLabels: {
            font: {
              size: 10,
              weight: "500",
            },
          },
          ticks: {
            font: {
              size: 9,
            },
            callback: (value) => value.toFixed(0) + " kg",
          },
        },
      },
      animation: {
        duration: 1500,
        easing: "easeOutQuart",
      },
    },
  });
}

/**
 * Carga la tabla de resumen por procesos
 */
function cargarTablaResumen(resumen) {
  const tbody = $("#tabla-resumen-procesos tbody");
  tbody.empty();

  if (resumen.length === 0) {
    tbody.append(`
      <tr>
        <td colspan="6" class="text-center">No hay datos disponibles para el período seleccionado</td>
      </tr>
    `);
    return;
  }

  resumen.forEach((proceso) => {
    const procesoClass = proceso.tipo.toLowerCase();
    const eficienciaClass =
      proceso.eficiencia >= 90
        ? "success"
        : proceso.eficiencia >= 70
        ? "warning"
        : "danger";

    tbody.append(`
      <tr>
        <td><span class="proceso-badge ${procesoClass}">${
      proceso.tipo
    }</span></td>
        <td>${proceso.registros}</td>
        <td>${proceso.produccion.toFixed(2)} t</td>
        <td>${proceso.ley_promedio.toFixed(2)} g/t</td>
        <td><span class="badge bg-${eficienciaClass}">${proceso.eficiencia.toFixed(
      1
    )}%</span></td>
        <td>${proceso.ultimo_registro}</td>
      </tr>
    `);
  });
}

/**
 * Actualiza los KPIs del dashboard
 */
function actualizarKPIs(kpis) {
  $("#kpi-crecimiento").text(kpis.crecimiento.toFixed(1) + "%");
  $("#kpi-precision").text(kpis.precision.toFixed(1) + "%");
  $("#kpi-tiempo-promedio").text(kpis.tiempoPromedio.toFixed(1) + "h");
  $("#kpi-completitud").text(kpis.completitud.toFixed(1) + "%");
}

/**
 * Funciones de exportación
 */
function exportarReporteCompleto() {
  const parametros = obtenerParametrosFiltro();
  const queryString = new URLSearchParams(parametros).toString();
  window.open(
    `../../../api/registros/estadistica/exportar.php?tipo=completo&${queryString}`,
    "_blank"
  );
}

function exportarGrafica(tipo) {
  let chart;
  switch (tipo) {
    case "produccion-tipo":
      chart = chartProduccionTipo;
      break;
    case "tendencia":
      chart = chartTendenciaProduccion;
      break;
    case "turnos":
      chart = chartDistribucionTurnos;
      break;
    case "leyes":
      chart = chartComparacionLeyes;
      break;
    case "eficiencia":
      chart = chartEficienciaLineas;
      break;
    case "productos":
      chart = chartProductosQuimicos;
      break;
  }

  if (chart) {
    const url = chart.toBase64Image();
    const link = document.createElement("a");
    link.download = `grafica-${tipo}-${
      new Date().toISOString().split("T")[0]
    }.png`;
    link.href = url;
    link.click();
  }
}

function exportarTabla(tipo) {
  const parametros = obtenerParametrosFiltro();
  const queryString = new URLSearchParams(parametros).toString();
  window.open(
    `../../../api/registros/estadistica/exportar.php?tipo=tabla&subtipo=${tipo}&${queryString}`,
    "_blank"
  );
}

function imprimirReporte() {
  window.print();
}

/**
 * Función para animar números
 */
function animarNumero(selector, valorFinal, duracion = 1500, decimales = 0) {
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

/**
 * Funciones de utilidad
 */
function mostrarCargando() {
  // Mostrar spinners en las tarjetas
  $(".stat-number").html('<div class="spinner"></div>');
  $(".card-content canvas").hide();
  $(".card-content").append(
    '<div class="text-center p-4"><div class="spinner"></div><span>Cargando...</span></div>'
  );
}

function ocultarCargando() {
  // Ocultar spinners
  $(".card-content .text-center").remove();
  $(".card-content canvas").show();
}

function mostrarError(mensaje) {
  console.error(mensaje);
  // Implementar notificación de error si existe el sistema de toast
  if (typeof window.showErrorToast === "function") {
    window.showErrorToast(mensaje);
  }
}

function actualizarTimestamp() {
  const ahora = new Date();
  const timestamp = ahora.toLocaleString("es-ES", {
    year: "numeric",
    month: "2-digit",
    day: "2-digit",
    hour: "2-digit",
    minute: "2-digit",
    second: "2-digit",
  });
  $("#ultima-actualizacion").text(timestamp);
  $("#fecha-generacion").text(ahora.toLocaleDateString("es-ES"));
}

// Funciones adicionales para interactividad
$(document).on("click", ".stat-card", function () {
  $(this).addClass("clicked");
  setTimeout(() => {
    $(this).removeClass("clicked");
  }, 200);
});

// Efecto de hover en las gráficas
$("canvas").hover(
  function () {
    $(this).css("cursor", "pointer");
  },
  function () {
    $(this).css("cursor", "default");
  }
);

// Configurar período inicial
$(document).ready(() => {
  // Establecer fechas por defecto para período personalizado
  const hoy = new Date();
  const hace30Dias = new Date();
  hace30Dias.setDate(hoy.getDate() - 30);

  $("#fecha-inicio").val(hace30Dias.toISOString().split("T")[0]);
  $("#fecha-fin").val(hoy.toISOString().split("T")[0]);
});
