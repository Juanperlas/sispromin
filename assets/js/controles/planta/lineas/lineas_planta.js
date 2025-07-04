/**
 * Gestión de líneas de planta
 * Funcionalidades para listar, crear y editar líneas de planta
 */

// Declaración de variables globales
const $ = window.jQuery;
const bootstrap = window.bootstrap;

// Variables globales
let lineasTable;
let lineaSeleccionada;
let modalLinea;
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

  // Inicializar DataTable
  function initDataTable() {
    lineasTable = $("#lineas-table").DataTable({
      processing: true,
      serverSide: true,
      responsive: true,
      ajax: {
        url: getUrl("api/controles/planta/lineas/listar.php"),
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
          data: "id",
          className: "align-middle text-center",
        },
        {
          data: "codigo",
          className: "align-middle text-center",
        },
        {
          data: "nombre",
          className: "align-middle",
        },
        {
          data: "fecha_creacion_formateada",
          className: "align-middle text-center",
        },
        {
          data: null,
          orderable: false,
          className: "text-center align-middle",
          render: (data) => {
            let acciones = '<div class="btn-group btn-group-sm">';
            acciones += `<button type="button" class="btn-accion btn-ver-linea" data-id="${data.id}" title="Ver detalles"><i class="bi bi-eye"></i></button>`;

            if (tienePermiso("controles.planta.lineas.editar")) {
              acciones += `<button type="button" class="btn-accion btn-editar-linea" data-id="${data.id}" title="Editar"><i class="bi bi-pencil"></i></button>`;
            }

            acciones += "</div>";
            return acciones;
          },
        },
      ],
      order: [[0, "desc"]], // Ordenar por ID descendente (más reciente primero)
      language: {
        url: getUrl("assets/plugins/datatables/js/es-ES.json"),
        loadingRecords: "Cargando...",
        processing: "Procesando...",
        zeroRecords: "No se encontraron registros",
        emptyTable: "No hay datos disponibles en la tabla",
      },
      dom: '<"row"<"col-md-6"B><"col-md-6"f>>rt<"row"<"col-md-6"l><"col-md-6"p>>',
      buttons: [
        {
          extend: "copy",
          text: '<i class="bi bi-clipboard"></i> Copiar',
          className: "btn btn-sm",
          exportOptions: {
            columns: [0, 1, 2, 3],
          },
        },
        {
          extend: "excel",
          text: '<i class="bi bi-file-earmark-excel"></i> Excel',
          className: "btn btn-sm",
          exportOptions: {
            columns: [0, 1, 2, 3],
          },
        },
        {
          extend: "pdf",
          text: '<i class="bi bi-file-earmark-pdf"></i> PDF',
          className: "btn btn-sm",
          exportOptions: {
            columns: [0, 1, 2, 3],
          },
          customize: (doc) => {
            doc.pageOrientation = "landscape";
            doc.defaultStyle = {
              fontSize: 8,
              color: "#333333",
            };

            const colores = {
              azulPastel: "#D4E6F1",
              azulOscuro: "#1A5276",
              azulPrimario: "#1571b0",
            };

            doc.content.unshift({
              text: "REPORTE DE LÍNEAS DE PLANTA",
              style: "header",
              alignment: "center",
              margin: [0, 15, 0, 15],
            });

            const tableIndex = doc.content.findIndex((item) => item.table);
            if (tableIndex !== -1) {
              doc.content[tableIndex].table.widths = [15, 25, 40, 20];

              doc.content[tableIndex].table.body.forEach((row, i) => {
                if (i === 0) {
                  row.forEach((cell) => {
                    cell.fillColor = colores.azulPastel;
                    cell.color = colores.azulOscuro;
                    cell.fontSize = 9;
                    cell.bold = true;
                    cell.alignment = "center";
                    cell.margin = [2, 3, 2, 3];
                  });
                } else {
                  row.forEach((cell, j) => {
                    cell.fontSize = 8;
                    cell.margin = [2, 2, 2, 2];
                    cell.alignment = j === 2 ? "left" : "center";
                  });

                  if (i % 2 === 0) {
                    row.forEach((cell) => {
                      cell.fillColor = "#f9f9f9";
                    });
                  }
                }
              });
            }

            doc.footer = (currentPage, pageCount) => ({
              text: `Página ${currentPage} de ${pageCount}`,
              alignment: "center",
              fontSize: 8,
              margin: [0, 0, 0, 0],
            });

            doc.styles = {
              header: {
                fontSize: 14,
                bold: true,
                color: colores.azulPrimario,
              },
            };

            doc.info = {
              title: "Reporte de Líneas de Planta",
              author: "SISPROMIN",
              subject: "Listado de Líneas de Planta",
            };
          },
          filename:
            "Reporte_Lineas_Planta_" + new Date().toISOString().split("T")[0],
          orientation: "landscape",
        },
        {
          extend: "print",
          text: '<i class="bi bi-printer"></i> Imprimir',
          className: "btn btn-sm",
          exportOptions: {
            columns: [0, 1, 2, 3],
          },
        },
      ],
      lengthMenu: [
        [10, 25, 50, 100, -1],
        [10, 25, 50, 100, "Todos"],
      ],
      pageLength: 25,
      initComplete: () => {
        // Evento para seleccionar fila
        $("#lineas-table tbody").on("click", "tr", function () {
          const data = lineasTable.row(this).data();
          if (data) {
            $("#lineas-table tbody tr").removeClass("selected");
            $(this).addClass("selected");

            $("#linea-detalle").removeClass("loaded").addClass("loading");

            setTimeout(() => {
              cargarDetallesLinea(data.id);
              $("#linea-detalle").removeClass("loading").addClass("loaded");
            }, 300);
          }
        });
      },
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

  // Función para cargar detalles de la línea en el panel lateral
  function cargarDetallesLinea(id) {
    $("#linea-detalle .detail-content").html(
      '<div class="text-center p-4"><div class="spinner-border text-primary" role="status"></div><p class="mt-2">Cargando detalles...</p></div>'
    );
    $("#linea-detalle").addClass("active");

    $.ajax({
      url: getUrl("api/controles/planta/lineas/obtener.php"),
      type: "GET",
      data: { id: id },
      dataType: "json",
      success: (response) => {
        if (response.success && response.data) {
          const data = response.data;
          lineaSeleccionada = data;

          $("#linea-detalle .detail-header").html(`
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h2 class="detail-title">Línea: ${data.nombre}</h2>
                                <p class="detail-subtitle">Código: ${data.codigo}</p>
                            </div>
                        </div>
                    `);

          const infoBasica = `
                        <div class="card-form mb-3">
                            <div class="card-form-header">
                                <i class="bi bi-info-circle me-2"></i>Información Básica
                            </div>
                            <div class="card-form-body">
                                <div class="row g-2">
                                    <div class="col-md-6">
                                        <div class="form-group mb-2">
                                            <label class="form-label form-label-sm">ID</label>
                                            <div class="form-control form-control-sm bg-light">${
                                              data.id
                                            }</div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group mb-2">
                                            <label class="form-label form-label-sm">Código</label>
                                            <div class="form-control form-control-sm bg-light">${
                                              data.codigo
                                            }</div>
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="form-group mb-2">
                                            <label class="form-label form-label-sm">Nombre</label>
                                            <div class="form-control form-control-sm bg-light">${
                                              data.nombre
                                            }</div>
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="form-group mb-2">
                                            <label class="form-label form-label-sm">Fecha de Creación</label>
                                            <div class="form-control form-control-sm bg-light">${formatearFecha(
                                              data.creado_en
                                            )}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;

          let botonEditar = "";
          if (tienePermiso("controles.planta.lineas.editar")) {
            botonEditar = `
                            <div class="d-grid gap-2 mt-3">
                                <button type="button" id="btn-editar-panel" class="btn btn-warning" data-id="${data.id}">
                                    <i class="bi bi-pencil me-2"></i>Editar Línea
                                </button>
                            </div>
                        `;
          }

          $("#linea-detalle .detail-content").html(`
                        ${infoBasica}
                        ${botonEditar}
                    `);

          $("#btn-editar-panel").on("click", function () {
            const id = $(this).data("id");
            abrirModalEditar(id);
          });
        } else {
          $("#linea-detalle .detail-content").html(`
                        <div class="detail-empty">
                            <div class="detail-empty-icon">
                                <i class="bi bi-exclamation-triangle"></i>
                            </div>
                            <div class="detail-empty-text">
                                Error al cargar los detalles de la línea
                            </div>
                        </div>
                    `);
        }
      },
      error: (xhr, status, error) => {
        $("#linea-detalle .detail-content").html(`
                    <div class="detail-empty">
                        <div class="detail-empty-icon">
                            <i class="bi bi-exclamation-triangle"></i>
                        </div>
                        <div class="detail-empty-text">
                            Error de conexión al servidor
                        </div>
                    </div>
                `);
        console.error("Error al obtener detalles de la línea:", error);
      },
    });
  }

  // Función para abrir modal de crear línea
  function abrirModalCrear() {
    $("#form-linea")[0].reset();
    $("#linea-id").val("");
    $("#modal-linea-titulo").text("Nueva Línea");

    modalLinea = new bootstrap.Modal(document.getElementById("modal-linea"));
    modalLinea.show();
  }

  // Función para abrir modal de editar línea
  function abrirModalEditar(id) {
    showLoadingOverlay();

    $.ajax({
      url: getUrl("api/controles/planta/lineas/obtener.php"),
      type: "GET",
      data: { id: id },
      dataType: "json",
      success: (response) => {
        hideLoadingOverlay();

        if (response.success && response.data) {
          const data = response.data;

          $("#linea-id").val(data.id);
          $("#linea-codigo").val(data.codigo);
          $("#linea-nombre").val(data.nombre);
          $("#modal-linea-titulo").text("Editar Línea");

          modalLinea = new bootstrap.Modal(
            document.getElementById("modal-linea")
          );
          modalLinea.show();
        } else {
          if (window.showErrorToast) {
            window.showErrorToast(
              response.message || "Error al obtener los datos de la línea"
            );
          }
        }
      },
      error: (xhr, status, error) => {
        hideLoadingOverlay();
        if (window.showErrorToast) {
          window.showErrorToast("Error de conexión al servidor");
        }
        console.error("Error al obtener línea:", error);
      },
    });
  }

  // Función para guardar línea
  function guardarLinea() {
    if (!validarFormulario()) {
      return;
    }

    showLoadingOverlay();

    const formData = new FormData();
    const id = $("#linea-id").val();

    if (id) {
      formData.append("id", id);
    }
    formData.append("codigo", $("#linea-codigo").val());
    formData.append("nombre", $("#linea-nombre").val());

    $.ajax({
      url: getUrl("api/controles/planta/lineas/guardar.php"),
      type: "POST",
      data: formData,
      processData: false,
      contentType: false,
      dataType: "json",
      success: (response) => {
        hideLoadingOverlay();

        if (response.success) {
          if (modalLinea) {
            modalLinea.hide();
          }

          if (window.showSuccessToast) {
            window.showSuccessToast(
              response.message || "Línea guardada correctamente"
            );
          }

          lineasTable.ajax.reload();

          if (lineaSeleccionada && lineaSeleccionada.id == id) {
            $("#linea-detalle").removeClass("loaded").addClass("loading");
            setTimeout(() => {
              cargarDetallesLinea(id);
              $("#linea-detalle").removeClass("loading").addClass("loaded");
            }, 300);
          }
        } else {
          if (window.showErrorToast) {
            window.showErrorToast(
              response.message || "Error al guardar la línea"
            );
          }
        }
      },
      error: (xhr, status, error) => {
        hideLoadingOverlay();
        if (window.showErrorToast) {
          window.showErrorToast("Error de conexión al servidor");
        }
        console.error("Error al guardar línea:", error);
      },
    });
  }

  // Función para validar el formulario
  function validarFormulario() {
    const codigo = $("#linea-codigo").val();
    if (!codigo) {
      if (window.showErrorToast) {
        window.showErrorToast("El código es obligatorio");
      }
      $("#linea-codigo").focus();
      return false;
    }

    const nombre = $("#linea-nombre").val();
    if (!nombre) {
      if (window.showErrorToast) {
        window.showErrorToast("El nombre es obligatorio");
      }
      $("#linea-nombre").focus();
      return false;
    }

    return true;
  }

  // Aplicar filtros
  function aplicarFiltros() {
    const codigo = $("#filtro-codigo").val();
    const nombre = $("#filtro-nombre").val();

    filtrosActivos = {};
    if (codigo) filtrosActivos.codigo = codigo;
    if (nombre) filtrosActivos.nombre = nombre;

    if (window.showInfoToast) {
      window.showInfoToast("Aplicando filtros...");
    }

    lineasTable.ajax.reload();

    $("#linea-detalle .detail-content").html(`
            <div class="detail-empty">
                <div class="detail-empty-icon">
                    <i class="bi bi-info-circle"></i>
                </div>
                <div class="detail-empty-text">
                    Seleccione una línea para ver sus detalles
                </div>
            </div>
        `);
    $("#linea-detalle").removeClass("active");
    lineaSeleccionada = null;
  }

  // Limpiar filtros
  function limpiarFiltros() {
    $("#filtro-codigo").val("");
    $("#filtro-nombre").val("");

    filtrosActivos = {};

    if (window.showInfoToast) {
      window.showInfoToast("Limpiando filtros...");
    }

    lineasTable.ajax.reload();

    $("#linea-detalle .detail-content").html(`
            <div class="detail-empty">
                <div class="detail-empty-icon">
                    <i class="bi bi-info-circle"></i>
                </div>
                <div class="detail-empty-text">
                    Seleccione una línea para ver sus detalles
                </div>
            </div>
        `);
    $("#linea-detalle").removeClass("active");
    lineaSeleccionada = null;
  }

  // Función para formatear fechas
  function formatearFecha(fecha) {
    if (!fecha) return "-";
    const date = new Date(fecha);
    return date.toLocaleDateString("es-ES", {
      day: "2-digit",
      month: "2-digit",
      year: "numeric",
      hour: "2-digit",
      minute: "2-digit",
    });
  }

  // Mostrar indicador de carga
  function showLoadingOverlay() {
    if (typeof window.showLoading === "function") {
      window.showLoading();
    }
  }

  // Ocultar indicador de carga
  function hideLoadingOverlay() {
    if (typeof window.hideLoading === "function") {
      window.hideLoading();
    }
  }

  // Verificar si el usuario tiene un permiso específico
  function tienePermiso(permiso) {
    if (typeof window.tienePermiso === "function") {
      return window.tienePermiso(permiso);
    }
    return true;
  }

  // Inicializar componentes
  initDataTable();

  // Event Listeners
  $("#btn-nueva-linea").on("click", () => {
    abrirModalCrear();
  });

  $("#btn-guardar-linea").on("click", () => {
    guardarLinea();
  });

  $(document).on("click", ".btn-ver-linea", function (e) {
    e.stopPropagation();
    const id = $(this).data("id");
    cargarDetallesLinea(id);
  });

  $(document).on("click", ".btn-editar-linea", function (e) {
    e.stopPropagation();
    const id = $(this).data("id");
    abrirModalEditar(id);
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
