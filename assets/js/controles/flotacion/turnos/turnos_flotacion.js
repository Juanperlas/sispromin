/**
 * Gestión de turnos de flotación
 * Funcionalidades para listar, crear y editar turnos de flotación
 * FUNCIONALIDAD: Panel lateral de detalles + Modal para ver detalles
 */

// Declaración de variables globales
const $ = window.jQuery;
const bootstrap = window.bootstrap;

// Variables globales
let turnosTable;
let turnoSeleccionado;
let modalTurno;
let modalVerTurno;
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

  // Array de IDs predefinidos que no se pueden editar.
  // ¡Este es un cambio clave que usaremos en múltiples lugares!
  const TURNOS_PREDEFINIDOS_IDS = [1, 2, 3, 4]; // Define los IDs aquí

  // Inicializar DataTable
  function initDataTable() {
    turnosTable = $("#turnos-table").DataTable({
      processing: true,
      serverSide: true,
      responsive: true,
      ajax: {
        url: getUrl("api/controles/flotacion/turnos/listar.php"),
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
            acciones += `<button type="button" class="btn-accion btn-ver-turno" data-id="${data.id}" title="Ver detalles"><i class="bi bi-eye"></i></button>`;

            // --- CAMBIO 1: Ocultar botón de editar en la tabla ---
            // Solo muestra el botón de editar si el usuario tiene permiso
            // Y el ID del turno NO está en la lista de turnos predefinidos.
            if (
              tienePermiso("controles.flotacion.turnos.editar") &&
              !TURNOS_PREDEFINIDOS_IDS.includes(data.id)
            ) {
              acciones += `<button type="button" class="btn-accion btn-editar-turno" data-id="${data.id}" title="Editar"><i class="bi bi-pencil"></i></button>`;
            }
            // --- FIN CAMBIO 1 ---

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
              text: "REPORTE DE TURNOS DE FLOTACIÓN",
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
              title: "Reporte de Turnos de Flotación",
              author: "SISPROMIN",
              subject: "Listado de Turnos de Flotación",
            };
          },
          filename:
            "Reporte_Turnos_Flotacion_" +
            new Date().toISOString().split("T")[0],
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
        $("#turnos-table tbody").on("click", "tr", function () {
          const data = turnosTable.row(this).data();
          if (data) {
            $("#turnos-table tbody tr").removeClass("selected");
            $(this).addClass("selected");

            $("#turno-detalle").removeClass("loaded").addClass("loading");

            setTimeout(() => {
              cargarDetallesTurno(data.id);
              $("#turno-detalle").removeClass("loading").addClass("loaded");
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

  // FUNCIONALIDAD: Función para mostrar modal con detalles del turno
  function mostrarModalDetalles(id) {
    modalVerTurno = new bootstrap.Modal(
      document.getElementById("modal-ver-turno")
    );

    // Resetear contenido del modal
    $("#modal-ver-turno-body").html(`
      <div class="text-center p-4">
        <div class="spinner-border text-primary" role="status"></div>
        <p class="mt-2">Cargando detalles...</p>
      </div>
    `);

    // --- CAMBIO 2: Ocultar botón de editar en el modal de detalles al iniciar la carga ---
    $("#btn-editar-desde-modal").hide();
    // --- FIN CAMBIO 2 ---

    modalVerTurno.show();

    $.ajax({
      url: getUrl("api/controles/flotacion/turnos/obtener.php"),
      type: "GET",
      data: { id: id },
      dataType: "json",
      success: (response) => {
        if (response.success && response.data) {
          const data = response.data;
          turnoSeleccionado = data;

          $("#modal-ver-turno-titulo").html(`
            <i class="bi bi-eye me-2"></i>Detalles del Turno: ${data.nombre}
          `);

          const contenidoModal = `
            <div class="row">
              <div class="col-12">
                <div class="card-form mb-3">
                  <div class="card-form-header">
                    <i class="bi bi-info-circle me-2"></i>Información Básica
                  </div>
                  <div class="card-form-body">
                    <div class="row g-3">
                      <div class="col-md-6">
                        <div class="form-group">
                          <label class="form-label form-label-sm fw-bold">ID</label>
                          <div class="form-control form-control-sm bg-light">${
                            data.id
                          }</div>
                        </div>
                      </div>
                      <div class="col-md-6">
                        <div class="form-group">
                          <label class="form-label form-label-sm fw-bold">Código</label>
                          <div class="form-control form-control-sm bg-light">${
                            data.codigo
                          }</div>
                        </div>
                      </div>
                      <div class="col-md-12">
                        <div class="form-group">
                          <label class="form-label form-label-sm fw-bold">Nombre</label>
                          <div class="form-control form-control-sm bg-light">${
                            data.nombre
                          }</div>
                        </div>
                      </div>
                      <div class="col-md-12">
                        <div class="form-group">
                          <label class="form-label form-label-sm fw-bold">Fecha de Creación</label>
                          <div class="form-control form-control-sm bg-light">${formatearFecha(
                            data.creado_en
                          )}</div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          `;

          $("#modal-ver-turno-body").html(contenidoModal);

          // --- CAMBIO 3: Lógica para mostrar/ocultar el botón "Editar desde Modal" ---
          // Solo muestra el botón de editar si el usuario tiene permisos
          // Y el ID del turno NO está en la lista de turnos predefinidos.
          if (
            tienePermiso("controles.flotacion.turnos.editar") &&
            !TURNOS_PREDEFINIDOS_IDS.includes(data.id)
          ) {
            $("#btn-editar-desde-modal")
              .show()
              .off("click")
              .on("click", () => {
                modalVerTurno.hide();
                setTimeout(() => {
                  abrirModalEditar(data.id);
                }, 300);
              });
          } else {
            $("#btn-editar-desde-modal").hide(); // Asegúrate de que esté oculto si no cumple la condición
          }
          // --- FIN CAMBIO 3 ---
        } else {
          $("#modal-ver-turno-body").html(`
            <div class="alert alert-danger">
              <i class="bi bi-exclamation-triangle me-2"></i>
              Error al cargar los detalles del turno
            </div>
          `);
        }
      },
      error: (xhr, status, error) => {
        $("#modal-ver-turno-body").html(`
          <div class="alert alert-danger">
            <i class="bi bi-exclamation-triangle me-2"></i>
            Error de conexión al servidor
          </div>
        `);
        console.error("Error al obtener detalles del turno:", error);
      },
    });
  }

  // Función para cargar detalles del turno en el panel lateral
  function cargarDetallesTurno(id) {
    $("#turno-detalle .detail-content").html(
      '<div class="text-center p-4"><div class="spinner-border text-primary" role="status"></div><p class="mt-2">Cargando detalles...</p></div>'
    );
    $("#turno-detalle").addClass("active");

    $.ajax({
      url: getUrl("api/controles/flotacion/turnos/obtener.php"),
      type: "GET",
      data: { id: id },
      dataType: "json",
      success: (response) => {
        if (response.success && response.data) {
          const data = response.data;
          turnoSeleccionado = data;

          $("#turno-detalle .detail-header").html(`
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h2 class="detail-title">Turno: ${data.nombre}</h2>
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
          // --- CAMBIO 4: Lógica para mostrar/ocultar el botón "Editar Turno" en el panel lateral ---
          // Solo genera el HTML del botón de editar si el usuario tiene permisos
          // Y el ID del turno NO está en la lista de turnos predefinidos.
          if (
            tienePermiso("controles.flotacion.turnos.editar") &&
            !TURNOS_PREDEFINIDOS_IDS.includes(data.id)
          ) {
            botonEditar = `
                                <div class="d-grid gap-2 mt-3">
                                    <button type="button" id="btn-editar-panel" class="btn btn-warning" data-id="${data.id}">
                                        <i class="bi bi-pencil me-2"></i>Editar Turno
                                    </button>
                                </div>
                            `;
          }
          // --- FIN CAMBIO 4 ---

          $("#turno-detalle .detail-content").html(`
                        ${infoBasica}
                        ${botonEditar}
                    `);

          // Solo adjunta el evento si el botón de editar fue generado
          if (
            tienePermiso("controles.flotacion.turnos.editar") &&
            !TURNOS_PREDEFINIDOS_IDS.includes(data.id)
          ) {
            $("#btn-editar-panel").on("click", function () {
              const id = $(this).data("id");
              abrirModalEditar(id);
            });
          }
        } else {
          $("#turno-detalle .detail-content").html(`
                        <div class="detail-empty">
                            <div class="detail-empty-icon">
                                <i class="bi bi-exclamation-triangle"></i>
                            </div>
                            <div class="detail-empty-text">
                                Error al cargar los detalles del turno
                            </div>
                        </div>
                    `);
        }
      },
      error: (xhr, status, error) => {
        $("#turno-detalle .detail-content").html(`
                    <div class="detail-empty">
                        <div class="detail-empty-icon">
                            <i class="bi bi-exclamation-triangle"></i>
                        </div>
                        <div class="detail-empty-text">
                            Error de conexión al servidor
                        </div>
                    </div>
                `);
        console.error("Error al obtener detalles del turno:", error);
      },
    });
  }

  // Función para abrir modal de crear turno
  function abrirModalCrear() {
    $("#form-turno")[0].reset();
    $("#turno-id").val("");
    $("#modal-turno-titulo").text("Nuevo Turno");

    modalTurno = new bootstrap.Modal(document.getElementById("modal-turno"));
    modalTurno.show();
  }

  // Función para abrir modal de editar turno
  function abrirModalEditar(id) {
    // --- CAMBIO 5: Prevenir la edición de IDs predefinidos al abrir el modal de edición directamente ---
    if (TURNOS_PREDEFINIDOS_IDS.includes(id)) {
      if (window.showErrorToast) {
        window.showErrorToast(
          "Este turno es predefinido y no se puede editar."
        );
      }
      return; // Detiene la ejecución si el ID es predefinido
    }
    // --- FIN CAMBIO 5 ---

    showLoadingOverlay();

    $.ajax({
      url: getUrl("api/controles/flotacion/turnos/obtener.php"),
      type: "GET",
      data: { id: id },
      dataType: "json",
      success: (response) => {
        hideLoadingOverlay();

        if (response.success && response.data) {
          const data = response.data;

          $("#turno-id").val(data.id);
          $("#turno-codigo").val(data.codigo);
          $("#turno-nombre").val(data.nombre);
          $("#modal-turno-titulo").text("Editar Turno");

          modalTurno = new bootstrap.Modal(
            document.getElementById("modal-turno")
          );
          modalTurno.show();
        } else {
          if (window.showErrorToast) {
            window.showErrorToast(
              response.message || "Error al obtener los datos del turno"
            );
          }
        }
      },
      error: (xhr, status, error) => {
        hideLoadingOverlay();
        if (window.showErrorToast) {
          window.showErrorToast("Error de conexión al servidor");
        }
        console.error("Error al obtener turno:", error);
      },
    });
  }

  // Función para guardar turno
  function guardarTurno() {
    if (!validarFormulario()) {
      return;
    }

    const id = $("#turno-id").val();
    // --- CAMBIO 6: Prevenir el guardado de IDs predefinidos si de alguna manera se manipuló el formulario ---
    if (id && TURNOS_PREDEFINIDOS_IDS.includes(parseInt(id))) {
      if (window.showErrorToast) {
        window.showErrorToast(
          "No se puede guardar cambios en un turno predefinido."
        );
      }
      hideLoadingOverlay(); // Asegúrate de ocultar el overlay si estaba visible
      return;
    }
    // --- FIN CAMBIO 6 ---

    showLoadingOverlay();

    const formData = new FormData();

    if (id) {
      formData.append("id", id);
    }
    formData.append("codigo", $("#turno-codigo").val());
    formData.append("nombre", $("#turno-nombre").val());

    $.ajax({
      url: getUrl("api/controles/flotacion/turnos/guardar.php"),
      type: "POST",
      data: formData,
      processData: false,
      contentType: false,
      dataType: "json",
      success: (response) => {
        hideLoadingOverlay();

        if (response.success) {
          if (modalTurno) {
            modalTurno.hide();
          }

          if (window.showSuccessToast) {
            window.showSuccessToast(
              response.message || "Turno guardado correctamente"
            );
          }

          turnosTable.ajax.reload();

          if (turnoSeleccionado && turnoSeleccionado.id == id) {
            $("#turno-detalle").removeClass("loaded").addClass("loading");
            setTimeout(() => {
              cargarDetallesTurno(id);
              $("#turno-detalle").removeClass("loading").addClass("loaded");
            }, 300);
          }
        } else {
          if (window.showErrorToast) {
            window.showErrorToast(
              response.message || "Error al guardar el turno"
            );
          }
        }
      },
      error: (xhr, status, error) => {
        hideLoadingOverlay();
        if (window.showErrorToast) {
          window.showErrorToast("Error de conexión al servidor");
        }
        console.error("Error al guardar turno:", error);
      },
    });
  }

  // Función para validar el formulario
  function validarFormulario() {
    const codigo = $("#turno-codigo").val();
    if (!codigo) {
      if (window.showErrorToast) {
        window.showErrorToast("El código es obligatorio");
      }
      $("#turno-codigo").focus();
      return false;
    }

    const nombre = $("#turno-nombre").val();
    if (!nombre) {
      if (window.showErrorToast) {
        window.showErrorToast("El nombre es obligatorio");
      }
      $("#turno-nombre").focus();
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

    turnosTable.ajax.reload();

    $("#turno-detalle .detail-content").html(`
            <div class="detail-empty">
                <div class="detail-empty-icon">
                    <i class="bi bi-info-circle"></i>
                </div>
                <div class="detail-empty-text">
                    Seleccione un turno para ver sus detalles
                </div>
            </div>
        `);
    $("#turno-detalle").removeClass("active");
    turnoSeleccionado = null;
  }

  // Limpiar filtros
  function limpiarFiltros() {
    $("#filtro-codigo").val("");
    $("#filtro-nombre").val("");

    filtrosActivos = {};

    if (window.showInfoToast) {
      window.showInfoToast("Limpiando filtros...");
    }

    turnosTable.ajax.reload();

    $("#turno-detalle .detail-content").html(`
            <div class="detail-empty">
                <div class="detail-empty-icon">
                    <i class="bi bi-info-circle"></i>
                </div>
                <div class="detail-empty-text">
                    Seleccione un turno para ver sus detalles
                </div>
            </div>
        `);
    $("#turno-detalle").removeClass("active");
    turnoSeleccionado = null;
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
  $("#btn-nuevo-turno").on("click", () => {
    abrirModalCrear();
  });

  $("#btn-guardar-turno").on("click", () => {
    guardarTurno();
  });

  // Event listener para el botón de ver detalles (modal)
  $(document).on("click", ".btn-ver-turno", function (e) {
    e.stopPropagation();
    const id = $(this).data("id");
    mostrarModalDetalles(id);
  });

  $(document).on("click", ".btn-editar-turno", function (e) {
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