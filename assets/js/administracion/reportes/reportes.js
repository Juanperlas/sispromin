/**
 * Gestión de reportes y auditoría general
 * Funcionalidades para visualizar auditoría diaria y efectividades
 */

// Declaración de variables globales
const $ = window.jQuery;
const bootstrap = window.bootstrap;

// Variables globales
let reportesTable;
let modalDetalle;
let filtrosActivos = {};

document.addEventListener("DOMContentLoaded", () => {
  // Función para obtener la URL base
  function getBaseUrl() {
    return window.location.pathname.split("/modulos/")[0] + "/";
  }

  // Función para construir URL completa
  function getUrl(path) {
    return getBaseUrl() + path;
  }

  // Inicializar componentes
  function inicializarComponentes() {
    // Inicializar datepickers
    $(".datepicker").datepicker({
      format: "dd/mm/yyyy",
      language: "es",
      autoclose: true,
      todayHighlight: true,
      orientation: "bottom auto",
    });
  }

  // Función para formatear números con separadores de miles
  function formatearNumero(numero, decimales = 2) {
    return Number.parseFloat(numero).toLocaleString("es-ES", {
      minimumFractionDigits: decimales,
      maximumFractionDigits: decimales,
    });
  }

  // Función para obtener badge de efectividad
  function obtenerBadgeEfectividad(efectividad, color) {
    let texto = "Sin datos";
    if (efectividad > 85) {
      texto = "Excelente";
    } else if (efectividad > 70) {
      texto = "Bueno";
    } else if (efectividad > 0) {
      texto = "Deficiente";
    }

    return `<span class="badge bg-${color} fs-6">${formatearNumero(
      efectividad,
      1
    )}%</span><br><small class="text-muted">${texto}</small>`;
  }

  // Inicializar DataTable
  function initDataTable() {
    reportesTable = $("#reportes-table").DataTable({
      processing: true,
      serverSide: true,
      responsive: true,
      ajax: {
        url: getUrl("api/administracion/reportes/datos.php"),
        type: "POST",
        data: (d) => {
          if (d.length == -1) {
            d.length = 10000;
          }
          return {
            ...d,
            ...filtrosActivos,
          };
        },
        error: (xhr, error, thrown) => {
          console.error(
            "Error en la solicitud AJAX de DataTable:",
            error,
            thrown
          );
          if (window.showErrorToast) {
            window.showErrorToast(
              "Error al cargar los datos de la tabla: " + thrown
            );
          }
        },
      },
      columns: [
        {
          data: "fecha_formateada",
          className: "align-middle text-center fw-bold",
          width: "12%",
        },
        {
          data: "total_mina",
          className: "align-middle text-end",
          width: "12%",
          render: (data, type, row) => {
            const valor = formatearNumero(data);
            const registros = row.registros_mina;
            return `<div class="text-primary fw-bold">${valor} g</div><small class="text-muted">${registros} registros</small>`;
          },
        },
        {
          data: "total_planta",
          className: "align-middle text-end",
          width: "12%",
          render: (data, type, row) => {
            const valor = formatearNumero(data);
            const registros = row.registros_planta;
            return `<div class="text-info fw-bold">${valor} g</div><small class="text-muted">${registros} registros</small>`;
          },
        },
        {
          data: "total_amalgamacion",
          className: "align-middle text-end",
          width: "12%",
          render: (data, type, row) => {
            const valor = formatearNumero(data);
            const registros = row.registros_amalgamacion;
            return `<div class="text-warning fw-bold">${valor} g</div><small class="text-muted">${registros} registros</small>`;
          },
        },
        {
          data: "total_flotacion",
          className: "align-middle text-end",
          width: "12%",
          render: (data, type, row) => {
            const valor = formatearNumero(data);
            const registros = row.registros_flotacion;
            return `<div class="text-secondary fw-bold">${valor} g</div><small class="text-muted">${registros} registros</small>`;
          },
        },
        {
          data: "efectividad_mina_planta",
          className: "align-middle text-center",
          width: "13%",
          render: (data, type, row) => {
            return obtenerBadgeEfectividad(
              data,
              row.efectividad_mina_planta_color
            );
          },
        },
        {
          data: "efectividad_mina_amalgamacion",
          className: "align-middle text-center",
          width: "13%",
          render: (data, type, row) => {
            return obtenerBadgeEfectividad(
              data,
              row.efectividad_mina_amalgamacion_color
            );
          },
        },
        {
          data: "efectividad_planta_amalgamacion",
          className: "align-middle text-center",
          width: "13%",
          render: (data, type, row) => {
            return obtenerBadgeEfectividad(
              data,
              row.efectividad_planta_amalgamacion_color
            );
          },
        },
        {
          data: null,
          orderable: false,
          className: "text-center align-middle",
          width: "8%",
          render: (data) => {
            return `<button type="button" class="btn btn-sm btn-outline-primary btn-ver-detalle" data-fecha="${data.fecha}" title="Ver auditoría completa">
                      <i class="bi bi-eye"></i>
                    </button>`;
          },
        },
      ],
      order: [[0, "desc"]], // Ordenar por fecha descendente
      language: {
        url: getUrl("assets/plugins/datatables/js/es-ES.json"),
        loadingRecords: "Cargando...",
        processing: "Procesando...",
        zeroRecords:
          "No se encontraron registros para las fechas seleccionadas",
        emptyTable: "No hay datos disponibles",
      },
      dom: '<"row"<"col-md-6"B><"col-md-6"f>>rt<"row"<"col-md-6"l><"col-md-6"p>>',
      buttons: [
        {
          extend: "excel",
          text: '<i class="bi bi-file-earmark-excel"></i> Excel',
          className: "btn btn-sm btn-success",
          exportOptions: {
            columns: [0, 1, 2, 3, 4, 5, 6, 7],
          },
          filename:
            "Auditoria_General_" + new Date().toISOString().split("T")[0],
        },
        {
          extend: "pdf",
          text: '<i class="bi bi-file-earmark-pdf"></i> PDF',
          className: "btn btn-sm btn-danger",
          exportOptions: {
            columns: [0, 1, 2, 3, 4, 5, 6, 7],
          },
          customize: (doc) => {
            doc.pageOrientation = "landscape";
            doc.defaultStyle = {
              fontSize: 8,
              color: "#333333",
            };

            doc.content.unshift({
              text: "AUDITORÍA GENERAL DIARIA - SISPROMIN",
              style: "header",
              alignment: "center",
              margin: [0, 15, 0, 15],
            });

            doc.styles = {
              header: {
                fontSize: 14,
                bold: true,
                color: "#1571b0",
              },
            };
          },
          filename:
            "Auditoria_General_" + new Date().toISOString().split("T")[0],
        },
      ],
      lengthMenu: [
        [10, 25, 50, 100],
        [10, 25, 50, 100],
      ],
      pageLength: 25,
      drawCallback: () => {
        if (window.hideLoading) {
          window.hideLoading();
        }
      },
      preDrawCallback: () => {
        if (window.showLoading) {
          window.showLoading();
        }
      },
    });
  }

  // Función para mostrar modal con detalles completos
  function mostrarModalDetalle(fecha) {
    modalDetalle = new bootstrap.Modal(
      document.getElementById("modal-detalle-auditoria")
    );

    // Resetear contenido del modal
    $("#modal-detalle-body").html(`
      <div class="text-center p-4">
        <div class="spinner-border text-primary" role="status"></div>
        <p class="mt-2">Cargando auditoría completa...</p>
      </div>
    `);

    modalDetalle.show();

    $.ajax({
      url: getUrl("api/administracion/reportes/obtener_detalle.php"),
      type: "GET",
      data: { fecha: fecha },
      dataType: "json",
      success: (response) => {
        if (response.success && response.data) {
          const data = response.data;

          $("#modal-detalle-titulo").html(`
            <i class="bi bi-calendar-check me-2"></i>Auditoría General - ${data.fecha_formateada}
          `);

          const contenidoModal = generarContenidoModalDetalle(data);
          $("#modal-detalle-body").html(contenidoModal);
        } else {
          $("#modal-detalle-body").html(`
            <div class="alert alert-danger">
              <i class="bi bi-exclamation-triangle me-2"></i>
              Error al cargar los detalles de la auditoría
            </div>
          `);
        }
      },
      error: (xhr, status, error) => {
        $("#modal-detalle-body").html(`
          <div class="alert alert-danger">
            <i class="bi bi-exclamation-triangle me-2"></i>
            Error de conexión al servidor
          </div>
        `);
        console.error("Error al obtener detalles de auditoría:", error);
      },
    });
  }

  // Generar contenido del modal de detalles
  function generarContenidoModalDetalle(data) {
    const resumen = data.resumen;
    const detalles = data.detalles;

    // Función para generar badge de efectividad
    function generarBadgeEfectividad(efectividad, color, texto) {
      return `<span class="badge bg-${color} fs-5 px-3 py-2">${formatearNumero(
        efectividad.efectividad,
        1
      )}% - ${efectividad.texto}</span>`;
    }

    // Resumen ejecutivo
    const resumenHtml = `
      <div class="row mb-4">
        <div class="col-12">
          <div class="card border-primary">
            <div class="card-header bg-primary text-white">
              <h5 class="mb-0"><i class="bi bi-graph-up me-2"></i>Resumen Ejecutivo</h5>
            </div>
            <div class="card-body">
              <div class="row g-3">
                <div class="col-md-3 text-center">
                  <div class="border rounded p-3 bg-light">
                    <h6 class="text-primary mb-1">MINA</h6>
                    <div class="fs-4 fw-bold text-primary">${formatearNumero(
                      resumen.total_mina
                    )} g</div>
                    <small class="text-muted">Producción Estimada</small>
                  </div>
                </div>
                <div class="col-md-3 text-center">
                  <div class="border rounded p-3 bg-light">
                    <h6 class="text-info mb-1">PLANTA</h6>
                    <div class="fs-4 fw-bold text-info">${formatearNumero(
                      resumen.total_planta
                    )} g</div>
                    <small class="text-muted">Producción Estimada</small>
                  </div>
                </div>
                <div class="col-md-3 text-center">
                  <div class="border rounded p-3 bg-light">
                    <h6 class="text-warning mb-1">AMALGAMACIÓN</h6>
                    <div class="fs-4 fw-bold text-warning">${formatearNumero(
                      resumen.total_amalgamacion
                    )} g</div>
                    <small class="text-muted">Resultado AU</small>
                  </div>
                </div>
                <div class="col-md-3 text-center">
                  <div class="border rounded p-3 bg-light">
                    <h6 class="text-secondary mb-1">FLOTACIÓN</h6>
                    <div class="fs-4 fw-bold text-secondary">${formatearNumero(
                      resumen.total_flotacion
                    )} g</div>
                    <small class="text-muted">Resultado Esperado</small>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="row mb-4">
        <div class="col-12">
          <div class="card border-success">
            <div class="card-header bg-success text-white">
              <h5 class="mb-0"><i class="bi bi-speedometer2 me-2"></i>Efectividades</h5>
            </div>
            <div class="card-body">
              <div class="row g-3 text-center">
                <div class="col-md-4">
                  <h6 class="mb-2">Mina → Planta</h6>
                  ${generarBadgeEfectividad(
                    resumen.efectividad_mina_planta,
                    resumen.efectividad_mina_planta.color,
                    resumen.efectividad_mina_planta.texto
                  )}
                  <div class="mt-2 small text-muted">
                    ${formatearNumero(
                      resumen.total_planta
                    )} / ${formatearNumero(resumen.total_mina)} × 100
                  </div>
                </div>
                <div class="col-md-4">
                  <h6 class="mb-2">Mina → Amalgamación</h6>
                  ${generarBadgeEfectividad(
                    resumen.efectividad_mina_amalgamacion,
                    resumen.efectividad_mina_amalgamacion.color,
                    resumen.efectividad_mina_amalgamacion.texto
                  )}
                  <div class="mt-2 small text-muted">
                    ${formatearNumero(
                      resumen.total_amalgamacion
                    )} / ${formatearNumero(resumen.total_mina)} × 100
                  </div>
                </div>
                <div class="col-md-4">
                  <h6 class="mb-2">Planta → Amalgamación</h6>
                  ${generarBadgeEfectividad(
                    resumen.efectividad_planta_amalgamacion,
                    resumen.efectividad_planta_amalgamacion.color,
                    resumen.efectividad_planta_amalgamacion.texto
                  )}
                  <div class="mt-2 small text-muted">
                    ${formatearNumero(
                      resumen.total_amalgamacion
                    )} / ${formatearNumero(resumen.total_planta)} × 100
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    `;

    // Detalles por área
    const detallesHtml = `
      <div class="row">
        <!-- MINA -->
        <div class="col-md-6 mb-3">
          <div class="card h-100">
            <div class="card-header bg-primary text-white">
              <h6 class="mb-0"><i class="bi bi-minecart me-2"></i>Detalle MINA (${
                detalles.mina.length
              } registros)</h6>
            </div>
            <div class="card-body p-2">
              <div class="table-responsive" style="max-height: 300px;">
                <table class="table table-sm table-striped">
                  <thead class="table-dark sticky-top">
                    <tr>
                      <th>Código</th>
                      <th>Turno</th>
                      <th>Frente</th>
                      <th>Material (t)</th>
                      <th>Ley (g/t)</th>
                      <th>Prod. Est. (g)</th>
                    </tr>
                  </thead>
                  <tbody>
                    ${detalles.mina
                      .map(
                        (item) => `
                      <tr>
                        <td class="small">${item.codigo_registro}</td>
                        <td class="small">${item.turno_nombre}</td>
                        <td class="small">${item.frente_nombre}</td>
                        <td class="small text-end">${formatearNumero(
                          item.material_extraido
                        )}</td>
                        <td class="small text-end">
                          ${
                            item.ley_laboratorio
                              ? `<span class="text-success">${formatearNumero(
                                  item.ley_laboratorio
                                )}</span>`
                              : `<span class="text-warning">${formatearNumero(
                                  item.ley_inferido_geologo
                                )}</span>`
                          }
                        </td>
                        <td class="small text-end fw-bold">${formatearNumero(
                          item.produccion_estimada
                        )}</td>
                      </tr>
                    `
                      )
                      .join("")}
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>

        <!-- PLANTA -->
        <div class="col-md-6 mb-3">
          <div class="card h-100">
            <div class="card-header bg-info text-white">
              <h6 class="mb-0"><i class="bi bi-building me-2"></i>Detalle PLANTA (${
                detalles.planta.length
              } registros)</h6>
            </div>
            <div class="card-body p-2">
              <div class="table-responsive" style="max-height: 300px;">
                <table class="table table-sm table-striped">
                  <thead class="table-dark sticky-top">
                    <tr>
                      <th>Código</th>
                      <th>Turno</th>
                      <th>Línea</th>
                      <th>Material (t)</th>
                      <th>Ley (g/t)</th>
                      <th>Prod. Est. (g)</th>
                    </tr>
                  </thead>
                  <tbody>
                    ${detalles.planta
                      .map(
                        (item) => `
                      <tr>
                        <td class="small">${item.codigo_registro}</td>
                        <td class="small">${item.turno_nombre}</td>
                        <td class="small">${item.linea_nombre}</td>
                        <td class="small text-end">${formatearNumero(
                          item.material_procesado
                        )}</td>
                        <td class="small text-end">
                          ${
                            item.ley_laboratorio
                              ? `<span class="text-success">${formatearNumero(
                                  item.ley_laboratorio
                                )}</span>`
                              : `<span class="text-warning">${formatearNumero(
                                  item.ley_inferido_metalurgista
                                )}</span>`
                          }
                        </td>
                        <td class="small text-end fw-bold">${formatearNumero(
                          item.produccion_estimada
                        )}</td>
                      </tr>
                    `
                      )
                      .join("")}
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>

        <!-- AMALGAMACIÓN -->
        <div class="col-md-6 mb-3">
          <div class="card h-100">
            <div class="card-header bg-warning text-dark">
              <h6 class="mb-0"><i class="bi bi-droplet me-2"></i>Detalle AMALGAMACIÓN (${
                detalles.amalgamacion.length
              } registros)</h6>
            </div>
            <div class="card-body p-2">
              <div class="table-responsive" style="max-height: 300px;">
                <table class="table table-sm table-striped">
                  <thead class="table-dark sticky-top">
                    <tr>
                      <th>Código</th>
                      <th>Turno</th>
                      <th>Línea</th>
                      <th>Amalg. (g)</th>
                      <th>Factor</th>
                      <th>Result. AU (g)</th>
                    </tr>
                  </thead>
                  <tbody>
                    ${detalles.amalgamacion
                      .map(
                        (item) => `
                      <tr>
                        <td class="small">${item.codigo_registro}</td>
                        <td class="small">${item.turno_nombre}</td>
                        <td class="small">${item.linea_nombre}</td>
                        <td class="small text-end">${formatearNumero(
                          item.amalgamacion_gramos
                        )}</td>
                        <td class="small text-end">${formatearNumero(
                          item.factor_conversion_amalg_au,
                          3
                        )}</td>
                        <td class="small text-end fw-bold">${formatearNumero(
                          item.resultado_au
                        )}</td>
                      </tr>
                    `
                      )
                      .join("")}
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>

        <!-- FLOTACIÓN -->
        <div class="col-md-6 mb-3">
          <div class="card h-100">
            <div class="card-header bg-secondary text-white">
              <h6 class="mb-0"><i class="bi bi-water me-2"></i>Detalle FLOTACIÓN (${
                detalles.flotacion.length
              } registros)</h6>
            </div>
            <div class="card-body p-2">
              <div class="table-responsive" style="max-height: 300px;">
                <table class="table table-sm table-striped">
                  <thead class="table-dark sticky-top">
                    <tr>
                      <th>Código</th>
                      <th>Turno</th>
                      <th>Carga Prom. (t)</th>
                      <th>Carga Extra (t)</th>
                      <th>Result. Esp. (g)</th>
                    </tr>
                  </thead>
                  <tbody>
                    ${detalles.flotacion
                      .map(
                        (item) => `
                      <tr>
                        <td class="small">${item.codigo_registro}</td>
                        <td class="small">${item.turno_nombre}</td>
                        <td class="small text-end">${formatearNumero(
                          item.carga_mineral_promedio
                        )}</td>
                        <td class="small text-end">${
                          item.carga_mineral_extra
                            ? formatearNumero(item.carga_mineral_extra)
                            : "-"
                        }</td>
                        <td class="small text-end fw-bold">${formatearNumero(
                          item.resultado_esperado
                        )}</td>
                      </tr>
                    `
                      )
                      .join("")}
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>
    `;

    return resumenHtml + detallesHtml;
  }

  // Aplicar filtros
  function aplicarFiltros() {
    const fechaInicio = $("#filtro-fecha-inicio").val();
    const fechaFin = $("#filtro-fecha-fin").val();

    filtrosActivos = {};
    if (fechaInicio) filtrosActivos.fecha_inicio = fechaInicio;
    if (fechaFin) filtrosActivos.fecha_fin = fechaFin;

    if (window.showInfoToast) {
      window.showInfoToast("Aplicando filtros...");
    }

    reportesTable.ajax.reload();
  }

  // Limpiar filtros
  function limpiarFiltros() {
    $("#filtro-fecha-inicio").val("");
    $("#filtro-fecha-fin").val("");

    filtrosActivos = {};

    if (window.showInfoToast) {
      window.showInfoToast("Mostrando solo el día actual...");
    }

    reportesTable.ajax.reload();
  }

  // Inicializar componentes
  inicializarComponentes();
  initDataTable();

  // Event Listeners
  $(document).on("click", ".btn-ver-detalle", function (e) {
    e.stopPropagation();
    const fecha = $(this).data("fecha");
    mostrarModalDetalle(fecha);
  });

  $("#btn-aplicar-filtros").on("click", () => {
    aplicarFiltros();
  });

  $("#btn-limpiar-filtros").on("click", () => {
    limpiarFiltros();
  });

  // Inicializar tooltips
  const tooltips = document.querySelectorAll("[title]");
  tooltips.forEach((tooltip) => {
    try {
      new bootstrap.Tooltip(tooltip);
    } catch (e) {
      console.warn("Error al inicializar tooltip:", e);
    }
  });
});

// Funciones de toast si no existen
if (!window.showErrorToast) {
  window.showErrorToast = (msg) => console.error(msg);
}
if (!window.showSuccessToast) {
  window.showSuccessToast = (msg) => console.log(msg);
}
if (!window.showInfoToast) {
  window.showInfoToast = (msg) => console.log(msg);
}
if (!window.showWarningToast) {
  window.showWarningToast = (msg) => console.warn(msg);
}
