/**
 * Gestión de frentes de mina
 * Funcionalidades para listar, crear y editar frentes de mina
 */

// Declaración de variables globales
const $ = window.jQuery;
const bootstrap = window.bootstrap;

// Variables globales
let frentesTable;
let frenteSeleccionado;
let modalFrente;
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
    frentesTable = $("#frentes-table").DataTable({
      processing: true,
      serverSide: true,
      responsive: true,
      ajax: {
        url: getUrl("api/controles/mina/frentes/listar.php"),
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
            acciones += `<button type="button" class="btn-accion btn-ver-frente" data-id="${data.id}" title="Ver detalles"><i class="bi bi-eye"></i></button>`;

            if (tienePermiso("controles.mina.frentes.editar")) {
              acciones += `<button type="button" class="btn-accion btn-editar-frente" data-id="${data.id}" title="Editar"><i class="bi bi-pencil"></i></button>`;
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
              text: "REPORTE DE FRENTES DE MINA",
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
              title: "Reporte de Frentes de Mina",
              author: "SISPROMIN",
              subject: "Listado de Frentes de Mina",
            };
          },
          filename:
            "Reporte_Frentes_Mina_" + new Date().toISOString().split("T")[0],
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
        $("#frentes-table tbody").on("click", "tr", function () {
          const data = frentesTable.row(this).data();
          if (data) {
            $("#frentes-table tbody tr").removeClass("selected");
            $(this).addClass("selected");

            $("#frente-detalle").removeClass("loaded").addClass("loading");

            setTimeout(() => {
              cargarDetallesFrente(data.id);
              $("#frente-detalle").removeClass("loading").addClass("loaded");
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

  // Función para cargar detalles del frente en el panel lateral
  function cargarDetallesFrente(id) {
    $("#frente-detalle .detail-content").html(
      '<div class="text-center p-4"><div class="spinner-border text-primary" role="status"></div><p class="mt-2">Cargando detalles...</p></div>'
    );
    $("#frente-detalle").addClass("active");

    $.ajax({
      url: getUrl("api/controles/mina/frentes/obtener.php"),
      type: "GET",
      data: { id: id },
      dataType: "json",
      success: (response) => {
        if (response.success && response.data) {
          const data = response.data;
          frenteSeleccionado = data;

          $("#frente-detalle .detail-header").html(`
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h2 class="detail-title">Frente: ${data.nombre}</h2>
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
          if (tienePermiso("controles.mina.frentes.editar")) {
            botonEditar = `
                            <div class="d-grid gap-2 mt-3">
                                <button type="button" id="btn-editar-panel" class="btn btn-warning" data-id="${data.id}">
                                    <i class="bi bi-pencil me-2"></i>Editar Frente
                                </button>
                            </div>
                        `;
          }

          $("#frente-detalle .detail-content").html(`
                        ${infoBasica}
                        ${botonEditar}
                    `);

          $("#btn-editar-panel").on("click", function () {
            const id = $(this).data("id");
            abrirModalEditar(id);
          });
        } else {
          $("#frente-detalle .detail-content").html(`
                        <div class="detail-empty">
                            <div class="detail-empty-icon">
                                <i class="bi bi-exclamation-triangle"></i>
                            </div>
                            <div class="detail-empty-text">
                                Error al cargar los detalles del frente
                            </div>
                        </div>
                    `);
        }
      },
      error: (xhr, status, error) => {
        $("#frente-detalle .detail-content").html(`
                    <div class="detail-empty">
                        <div class="detail-empty-icon">
                            <i class="bi bi-exclamation-triangle"></i>
                        </div>
                        <div class="detail-empty-text">
                            Error de conexión al servidor
                        </div>
                    </div>
                `);
        console.error("Error al obtener detalles del frente:", error);
      },
    });
  }

  // Función para abrir modal de crear frente
  function abrirModalCrear() {
    $("#form-frente")[0].reset();
    $("#frente-id").val("");
    $("#modal-frente-titulo").text("Nuevo Frente");

    modalFrente = new bootstrap.Modal(document.getElementById("modal-frente"));
    modalFrente.show();
  }

  // Función para abrir modal de editar frente
  function abrirModalEditar(id) {
    showLoadingOverlay();

    $.ajax({
      url: getUrl("api/controles/mina/frentes/obtener.php"),
      type: "GET",
      data: { id: id },
      dataType: "json",
      success: (response) => {
        hideLoadingOverlay();

        if (response.success && response.data) {
          const data = response.data;

          $("#frente-id").val(data.id);
          $("#frente-codigo").val(data.codigo);
          $("#frente-nombre").val(data.nombre);
          $("#modal-frente-titulo").text("Editar Frente");

          modalFrente = new bootstrap.Modal(
            document.getElementById("modal-frente")
          );
          modalFrente.show();
        } else {
          if (window.showErrorToast) {
            window.showErrorToast(
              response.message || "Error al obtener los datos del frente"
            );
          }
        }
      },
      error: (xhr, status, error) => {
        hideLoadingOverlay();
        if (window.showErrorToast) {
          window.showErrorToast("Error de conexión al servidor");
        }
        console.error("Error al obtener frente:", error);
      },
    });
  }

  // Función para guardar frente
  function guardarFrente() {
    if (!validarFormulario()) {
      return;
    }

    showLoadingOverlay();

    const formData = new FormData();
    const id = $("#frente-id").val();

    if (id) {
      formData.append("id", id);
    }
    formData.append("codigo", $("#frente-codigo").val());
    formData.append("nombre", $("#frente-nombre").val());

    $.ajax({
      url: getUrl("api/controles/mina/frentes/guardar.php"),
      type: "POST",
      data: formData,
      processData: false,
      contentType: false,
      dataType: "json",
      success: (response) => {
        hideLoadingOverlay();

        if (response.success) {
          if (modalFrente) {
            modalFrente.hide();
          }

          if (window.showSuccessToast) {
            window.showSuccessToast(
              response.message || "Frente guardado correctamente"
            );
          }

          frentesTable.ajax.reload();

          if (frenteSeleccionado && frenteSeleccionado.id == id) {
            $("#frente-detalle").removeClass("loaded").addClass("loading");
            setTimeout(() => {
              cargarDetallesFrente(id);
              $("#frente-detalle").removeClass("loading").addClass("loaded");
            }, 300);
          }
        } else {
          if (window.showErrorToast) {
            window.showErrorToast(
              response.message || "Error al guardar el frente"
            );
          }
        }
      },
      error: (xhr, status, error) => {
        hideLoadingOverlay();
        if (window.showErrorToast) {
          window.showErrorToast("Error de conexión al servidor");
        }
        console.error("Error al guardar frente:", error);
      },
    });
  }

  // Función para validar el formulario
  function validarFormulario() {
    const codigo = $("#frente-codigo").val();
    if (!codigo) {
      if (window.showErrorToast) {
        window.showErrorToast("El código es obligatorio");
      }
      $("#frente-codigo").focus();
      return false;
    }

    const nombre = $("#frente-nombre").val();
    if (!nombre) {
      if (window.showErrorToast) {
        window.showErrorToast("El nombre es obligatorio");
      }
      $("#frente-nombre").focus();
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

    frentesTable.ajax.reload();

    $("#frente-detalle .detail-content").html(`
            <div class="detail-empty">
                <div class="detail-empty-icon">
                    <i class="bi bi-info-circle"></i>
                </div>
                <div class="detail-empty-text">
                    Seleccione un frente para ver sus detalles
                </div>
            </div>
        `);
    $("#frente-detalle").removeClass("active");
    frenteSeleccionado = null;
  }

  // Limpiar filtros
  function limpiarFiltros() {
    $("#filtro-codigo").val("");
    $("#filtro-nombre").val("");

    filtrosActivos = {};

    if (window.showInfoToast) {
      window.showInfoToast("Limpiando filtros...");
    }

    frentesTable.ajax.reload();

    $("#frente-detalle .detail-content").html(`
            <div class="detail-empty">
                <div class="detail-empty-icon">
                    <i class="bi bi-info-circle"></i>
                </div>
                <div class="detail-empty-text">
                    Seleccione un frente para ver sus detalles
                </div>
            </div>
        `);
    $("#frente-detalle").removeClass("active");
    frenteSeleccionado = null;
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
  $("#btn-nuevo-frente").on("click", () => {
    abrirModalCrear();
  });

  $("#btn-guardar-frente").on("click", () => {
    guardarFrente();
  });

  $(document).on("click", ".btn-ver-frente", function (e) {
    e.stopPropagation();
    const id = $(this).data("id");
    cargarDetallesFrente(id);
  });

  $(document).on("click", ".btn-editar-frente", function (e) {
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
