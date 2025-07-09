/**
 * Gestión de registros de flotación
 * Funcionalidades para listar, crear, editar y eliminar registros de flotación
 */

// Declaración de variables globales
const $ = window.jQuery;
const bootstrap = window.bootstrap;

// Variables globales
let flotacionTable;
let registroSeleccionado;
let modalRegistro;
let modalVerRegistro;
let filtrosActivos = {};
let turnos = [];
let productos = [];
let productosSeleccionados = [];

document.addEventListener("DOMContentLoaded", () => {
  // Función para obtener la URL base
  function getBaseUrl() {
    return window.location.pathname.split("/modulos/")[0] + "/";
  }

  // Función para construir URL completa
  function getUrl(path) {
    return getBaseUrl() + path;
  }

  // Función para obtener el indicador de estado del cálculo
  function obtenerIndicadorEstado(estadoCalculo, resultadoEsperado) {
    const valor = Number.parseFloat(resultadoEsperado).toFixed(2);

    switch (estadoCalculo) {
      case "completo":
        return `<span class="resultado-completo" title="Cálculo completo con todos los datos">${valor} g ✓</span>`;
      case "completo_sin_extra":
        return `<span class="resultado-completo-sin-extra" title="Cálculo completo (sin material extra)">${valor} g ✓</span>`;
      case "sin_laboratorio":
        return `<span class="resultado-incompleto" title="Falta ley de laboratorio">${valor} g ⚠️</span>`;
      case "falta_ley_extra":
        return `<span class="resultado-incompleto" title="Falta ley del material extra">${valor} g ⚠️</span>`;
      case "parcial":
        return `<span class="resultado-parcial" title="Cálculo parcial - faltan datos">${valor} g ⚠️</span>`;
      default:
        return `<span class="resultado-desconocido">${valor} g</span>`;
    }
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

    // Cargar datos iniciales
    cargarTurnos();
    cargarProductos();

    // Manejar checkbox de laboratorio
    $("#registro-incluir-laboratorio").on("change", function () {
      if ($(this).is(":checked")) {
        $("#grupo-codigo-muestra, #grupo-ley-laboratorio").show();
      } else {
        $("#grupo-codigo-muestra, #grupo-ley-laboratorio").hide();
        $("#registro-codigo-muestra, #registro-ley-laboratorio").val("");
      }
    });
  }

  // Cargar turnos
  function cargarTurnos() {
    $.ajax({
      url: getUrl("api/registros/flotacion/obtener_turnos.php"),
      type: "GET",
      dataType: "json",
      success: (response) => {
        const selectTurno = $("#registro-turno, #filtro-turno");

        // Limpiar opciones existentes (excepto la primera)
        selectTurno.find("option:not(:first)").remove();

        if (response.success && response.data && response.data.length > 0) {
          turnos = response.data;

          // Agregar nuevas opciones
          turnos.forEach((turno) => {
            selectTurno.append(
              `<option value="${turno.id}">${turno.nombre} (${turno.codigo})</option>`
            );
          });
        } else {
          // No hay datos - mostrar opción para agregar
          selectTurno.append(
            `<option value="" disabled>No hay turnos disponibles</option>`
          );
          selectTurno.append(
            `<option value="add-new" style="color: #007bff; font-weight: bold;">+ Agregar nuevo turno</option>`
          );

          if (window.showWarningToast) {
            window.showWarningToast(
              "No hay turnos configurados. Configure los turnos primero."
            );
          }
        }
      },
      error: (xhr, status, error) => {
        console.error("Error al cargar turnos:", error);

        const selectTurno = $("#registro-turno, #filtro-turno");
        selectTurno.find("option:not(:first)").remove();
        selectTurno.append(
          `<option value="" disabled>Error al cargar turnos</option>`
        );
        selectTurno.append(
          `<option value="add-new" style="color: #007bff; font-weight: bold;">+ Agregar nuevo turno</option>`
        );

        if (window.showErrorToast) {
          window.showErrorToast("Error al cargar los turnos: " + error);
        }
      },
    });
  }

  // Cargar productos
  function cargarProductos() {
    $.ajax({
      url: getUrl("api/registros/flotacion/obtener_productos.php"),
      type: "GET",
      dataType: "json",
      success: (response) => {
        if (response.success && response.data && response.data.length > 0) {
          productos = response.data;
        } else {
          productos = [];
          if (window.showWarningToast) {
            window.showWarningToast(
              "No hay productos configurados. Configure los productos primero."
            );
          }
        }
      },
      error: (xhr, status, error) => {
        console.error("Error al cargar productos:", error);
        productos = [];
        if (window.showErrorToast) {
          window.showErrorToast("Error al cargar los productos: " + error);
        }
      },
    });
  }

  // Manejar selección de "agregar nuevo" en turnos
  $(document).on("change", "#registro-turno, #filtro-turno", function () {
    if ($(this).val() === "add-new") {
      if (
        confirm("¿Desea ir al módulo de turnos para agregar un nuevo turno?")
      ) {
        window.open(
          getBaseUrl() + "modulos/controles/flotacion/turnos/index.php",
          "_blank"
        );
      }
      $(this).val(""); // Resetear selección
    }
  });

  // Agregar producto químico
  $("#btn-agregar-producto").on("click", () => {
    agregarProducto();
  });

  function agregarProducto(productoId = "", cantidad = "") {
    const index = productosSeleccionados.length;

    let optionsHtml = '<option value="">Seleccionar producto</option>';
    productos.forEach((producto) => {
      const selected = producto.id == productoId ? "selected" : "";
      optionsHtml += `<option value="${producto.id}" ${selected}>${producto.nombre} (${producto.codigo})</option>`;
    });

    const productoHtml = `
      <div class="producto-item" data-index="${index}">
        <div class="row g-2">
          <div class="col-md-8">
            <div class="form-group mb-0">
              <label class="form-label form-label-sm">Producto Químico</label>
              <select class="form-control form-control-sm producto-select" name="productos[${index}][producto_id]" required>
                ${optionsHtml}
              </select>
            </div>
          </div>
          <div class="col-md-3">
            <div class="form-group mb-0">
              <label class="form-label form-label-sm">Cantidad</label>
              <input type="number" class="form-control form-control-sm" name="productos[${index}][cantidad]" 
                     step="0.01" min="0" value="${cantidad}" placeholder="0.00">
            </div>
          </div>
          <div class="col-md-1">
            <button type="button" class="btn-remove-producto" title="Eliminar producto">
              <i class="bi bi-trash"></i>
            </button>
          </div>
        </div>
      </div>
    `;

    if ($("#productos-container .text-muted").length > 0) {
      $("#productos-container").html(productoHtml);
    } else {
      $("#productos-container").append(productoHtml);
    }

    productosSeleccionados.push({
      producto_id: productoId,
      cantidad: cantidad,
    });
  }

  // Remover producto químico
  $(document).on("click", ".btn-remove-producto", function () {
    const productoItem = $(this).closest(".producto-item");
    const index = productoItem.data("index");

    productoItem.remove();
    productosSeleccionados.splice(index, 1);

    if ($("#productos-container .producto-item").length === 0) {
      $("#productos-container").html(`
        <div class="text-muted text-center p-3">
          <i class="bi bi-info-circle me-2"></i>
          Los productos se cargarán automáticamente
        </div>
      `);
    }
  });

  // Inicializar DataTable
  function initDataTable() {
    flotacionTable = $("#flotacion-table").DataTable({
      processing: true,
      serverSide: true,
      responsive: true,
      ajax: {
        url: getUrl("api/registros/flotacion/listar.php"),
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
          data: "fecha_formateada",
          className: "align-middle text-center",
        },
        {
          data: "codigo_registro",
          className: "align-middle text-center",
        },
        {
          data: "turno_nombre",
          className: "align-middle text-center",
        },
        {
          data: "carga_mineral_promedio",
          className: "align-middle text-center",
          render: (data) => {
            return data ? Number.parseFloat(data).toFixed(2) + " t" : "0.00 t";
          },
        },
        {
          data: "ley_laboratorio",
          className: "align-middle text-center",
          render: (data, type, row) => {
            if (data && data > 0) {
              return `<span class="lab-indicator has-lab"></span>${Number.parseFloat(
                data
              ).toFixed(2)} g/t`;
            } else {
              return `<span class="lab-indicator no-lab"></span>-`;
            }
          },
        },
        {
          data: "carga_mineral_extra",
          className: "align-middle text-center",
          render: (data) => {
            return data ? Number.parseFloat(data).toFixed(2) + " t" : "-";
          },
        },
        {
          data: "ley_inferido_metalurgista_extra",
          className: "align-middle text-center",
          render: (data) => {
            return data ? Number.parseFloat(data).toFixed(2) + " g/t" : "-";
          },
        },
        {
          data: "resultado_esperado",
          className: "align-middle text-center valor-calculado",
          render: (data, type, row) => {
            return obtenerIndicadorEstado(row.estado_calculo, data);
          },
        },
        {
          data: "calificacion",
          className: "align-middle text-center",
          render: (data) => {
            const calificacion = data ? Number.parseFloat(data) : 0;
            let colorClass = "bg-danger";

            if (calificacion > 85) {
              colorClass = "bg-success";
            } else if (calificacion > 70) {
              colorClass = "bg-warning";
            }

            return `<span class="badge ${colorClass}">${calificacion.toFixed(
              1
            )}%</span>`;
          },
        },
        {
          data: null,
          orderable: false,
          className: "text-center align-middle",
          render: (data) => {
            let acciones = '<div class="btn-group btn-group-sm">';
            acciones += `<button type="button" class="btn-accion btn-ver-registro" data-id="${data.id}" title="Ver detalles"><i class="bi bi-eye"></i></button>`;

            if (tienePermiso("registros.flotacion.editar")) {
              acciones += `<button type="button" class="btn-accion btn-editar-registro" data-id="${data.id}" title="Editar"><i class="bi bi-pencil"></i></button>`;
            }

            if (tienePermiso("registros.flotacion.eliminar")) {
              acciones += `<button type="button" class="btn-accion btn-eliminar-registro" data-id="${data.id}" title="Eliminar"><i class="bi bi-trash"></i></button>`;
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
            columns: [0, 1, 2, 3, 4, 5, 6, 7, 8, 9],
          },
        },
        {
          extend: "excel",
          text: '<i class="bi bi-file-earmark-excel"></i> Excel',
          className: "btn btn-sm",
          exportOptions: {
            columns: [0, 1, 2, 3, 4, 5, 6, 7, 8, 9],
          },
        },
        {
          extend: "pdf",
          text: '<i class="bi bi-file-earmark-pdf"></i> PDF',
          className: "btn btn-sm",
          exportOptions: {
            columns: [0, 1, 2, 3, 4, 5, 6, 7, 8, 9],
          },
          customize: (doc) => {
            doc.pageOrientation = "landscape";
            doc.defaultStyle = {
              fontSize: 6,
              color: "#333333",
            };

            const colores = {
              azulPastel: "#D4E6F1",
              azulOscuro: "#1A5276",
              azulPrimario: "#1571b0",
            };

            doc.content.unshift({
              text: "REPORTE DE FLOTACIÓN",
              style: "header",
              alignment: "center",
              margin: [0, 15, 0, 15],
            });

            const tableIndex = doc.content.findIndex((item) => item.table);
            if (tableIndex !== -1) {
              doc.content[tableIndex].table.widths = [
                6, 8, 12, 8, 10, 10, 10, 10, 10, 8,
              ];

              doc.content[tableIndex].table.body.forEach((row, i) => {
                if (i === 0) {
                  row.forEach((cell) => {
                    cell.fillColor = colores.azulPastel;
                    cell.color = colores.azulOscuro;
                    cell.fontSize = 7;
                    cell.bold = true;
                    cell.alignment = "center";
                    cell.margin = [1, 2, 1, 2];
                  });
                } else {
                  row.forEach((cell, j) => {
                    cell.fontSize = 6;
                    cell.margin = [1, 1, 1, 1];
                    if (j === 1 || j === 2 || j === 3) {
                      cell.alignment = "center";
                    } else if (j >= 4) {
                      cell.alignment = "right";
                    } else {
                      cell.alignment = "center";
                    }
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
              fontSize: 7,
              margin: [0, 0, 0, 0],
            });

            doc.styles = {
              header: {
                fontSize: 12,
                bold: true,
                color: colores.azulPrimario,
              },
            };

            doc.info = {
              title: "Reporte de Flotación",
              author: "SISPROMIN",
              subject: "Listado de Registros de Flotación",
            };
          },
          filename:
            "Reporte_Flotacion_" + new Date().toISOString().split("T")[0],
          orientation: "landscape",
        },
        {
          extend: "print",
          text: '<i class="bi bi-printer"></i> Imprimir',
          className: "btn btn-sm",
          exportOptions: {
            columns: [0, 1, 2, 3, 4, 5, 6, 7, 8, 9],
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
        $("#flotacion-table tbody").on("click", "tr", function () {
          const data = flotacionTable.row(this).data();
          if (data) {
            $("#flotacion-table tbody tr").removeClass("selected");
            $(this).addClass("selected");

            $("#registro-detalle").removeClass("loaded").addClass("loading");

            setTimeout(() => {
              cargarDetallesRegistro(data.id);
              $("#registro-detalle").removeClass("loading").addClass("loaded");
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

  // Función para mostrar modal con detalles del registro
  function mostrarModalDetalles(id) {
    modalVerRegistro = new bootstrap.Modal(
      document.getElementById("modal-ver-registro")
    );

    // Resetear contenido del modal
    $("#modal-ver-registro-body").html(`
      <div class="text-center p-4">
        <div class="spinner-border text-primary" role="status"></div>
        <p class="mt-2">Cargando detalles...</p>
      </div>
    `);

    $("#btn-editar-desde-modal").hide();
    modalVerRegistro.show();

    $.ajax({
      url: getUrl("api/registros/flotacion/obtener.php"),
      type: "GET",
      data: { id: id },
      dataType: "json",
      success: (response) => {
        if (response.success && response.data) {
          const data = response.data;
          registroSeleccionado = data;

          $("#modal-ver-registro-titulo").html(`
            <i class="bi bi-eye me-2"></i>Detalles del Registro: ${data.codigo_registro}
          `);

          const contenidoModal = generarContenidoModalDetalle(data);
          $("#modal-ver-registro-body").html(contenidoModal);

          // Mostrar botón de editar si tiene permisos
          if (tienePermiso("registros.flotacion.editar")) {
            $("#btn-editar-desde-modal")
              .show()
              .off("click")
              .on("click", () => {
                modalVerRegistro.hide();
                setTimeout(() => {
                  abrirModalEditar(data.id);
                }, 300);
              });
          }
        } else {
          $("#modal-ver-registro-body").html(`
            <div class="alert alert-danger">
              <i class="bi bi-exclamation-triangle me-2"></i>
              Error al cargar los detalles del registro
            </div>
          `);
        }
      },
      error: (xhr, status, error) => {
        $("#modal-ver-registro-body").html(`
          <div class="alert alert-danger">
            <i class="bi bi-exclamation-triangle me-2"></i>
            Error de conexión al servidor
          </div>
        `);
        console.error("Error al obtener detalles del registro:", error);
      },
    });
  }

  // Generar contenido del modal de detalles
  function generarContenidoModalDetalle(data) {
    const resultadoEsperado = data.resultado_esperado
      ? Number.parseFloat(data.resultado_esperado).toFixed(2)
      : "0.00";
    const calificacion = data.calificacion
      ? Number.parseFloat(data.calificacion).toFixed(1)
      : "0.0";

    // Generar mensaje de estado del cálculo
    let estadoMensaje = "";
    let estadoColor = "bg-success";

    switch (data.estado_calculo) {
      case "completo":
        estadoMensaje = "Cálculo completo con todos los datos";
        estadoColor = "bg-success";
        break;
      case "completo_sin_extra":
        estadoMensaje = "Cálculo completo (sin material extra)";
        estadoColor = "bg-info";
        break;
      case "sin_laboratorio":
        estadoMensaje = "⚠️ Falta ley de laboratorio";
        estadoColor = "bg-warning";
        break;
      case "falta_ley_extra":
        estadoMensaje = "⚠️ Falta ley del material extra";
        estadoColor = "bg-warning";
        break;
      case "parcial":
        estadoMensaje = "⚠️ Cálculo parcial - faltan datos";
        estadoColor = "bg-warning";
        break;
      default:
        estadoMensaje = "Estado desconocido";
        estadoColor = "bg-secondary";
    }

    return `
      <div class="row">
        <div class="col-md-6">
          <div class="card-form mb-3">
            <div class="card-form-header">
              <i class="bi bi-info-circle me-2"></i>Información Básica
            </div>
            <div class="card-form-body">
              <div class="row g-3">
                <div class="col-md-6">
                  <div class="form-group">
                    <label class="form-label form-label-sm fw-bold">N°</label>
                    <div class="form-control form-control-sm bg-light">${
                      data.id
                    }</div>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group">
                    <label class="form-label form-label-sm fw-bold">Código</label>
                    <div class="form-control form-control-sm bg-light">${
                      data.codigo_registro
                    }</div>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group">
                    <label class="form-label form-label-sm fw-bold">Fecha</label>
                    <div class="form-control form-control-sm bg-light">${
                      data.fecha_formateada
                    }</div>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group">
                    <label class="form-label form-label-sm fw-bold">Turno</label>
                    <div class="form-control form-control-sm bg-light">${
                      data.turno_nombre
                    }</div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-md-6">
          <div class="card-form mb-3">
            <div class="card-form-header">
              <i class="bi bi-box-seam me-2"></i>Datos de Carga
            </div>
            <div class="card-form-body">
              <div class="row g-3">
                <div class="col-md-6">
                  <div class="form-group">
                    <label class="form-label form-label-sm fw-bold">Carga Promedio</label>
                    <div class="form-control form-control-sm bg-light">${Number.parseFloat(
                      data.carga_mineral_promedio
                    ).toFixed(2)} t</div>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group">
                    <label class="form-label form-label-sm fw-bold">Carga Extra</label>
                    <div class="form-control form-control-sm bg-light">${
                      data.carga_mineral_extra
                        ? Number.parseFloat(data.carga_mineral_extra).toFixed(
                            2
                          ) + " t"
                        : "-"
                    }</div>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="form-group">
                    <label class="form-label form-label-sm fw-bold">Código Muestra Extra</label>
                    <div class="form-control form-control-sm bg-light">${
                      data.codigo_muestra_mat_extra || "-"
                    }</div>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="form-group">
                    <label class="form-label form-label-sm fw-bold">Ley Extra</label>
                    <div class="form-control form-control-sm bg-light">${
                      data.ley_inferido_metalurgista_extra
                        ? Number.parseFloat(
                            data.ley_inferido_metalurgista_extra
                          ).toFixed(2) + " g/t"
                        : "-"
                    }</div>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="form-group">
                    <label class="form-label form-label-sm fw-bold">Ley Laboratorio</label>
                    <div class="form-control form-control-sm bg-light">${
                      data.ley_laboratorio
                        ? Number.parseFloat(data.ley_laboratorio).toFixed(2) +
                          " g/t"
                        : "-"
                    }</div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-md-6">
          <div class="card-form mb-3">
            <div class="card-form-header">
              <i class="bi bi-calculator me-2"></i>Resultados
            </div>
            <div class="card-form-body">
              <div class="row g-3">
                <div class="col-md-12">
                  <div class="form-group">
                    <label class="form-label form-label-sm fw-bold">Resultado Esperado</label>
                    <div class="form-control form-control-sm ${estadoColor} text-white fw-bold">${resultadoEsperado} g</div>
                    <small class="text-muted">${estadoMensaje}</small>
                  </div>
                </div>
                <div class="col-md-12">
                  <div class="form-group">
                    <label class="form-label form-label-sm fw-bold">Calificación</label>
                    <div class="form-control form-control-sm bg-warning text-dark fw-bold">${calificacion}%</div>
                    <small class="text-muted">Calificación del resultado obtenido</small>
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
        <div class="col-md-6">
          <div class="card-form mb-3">
            <div class="card-form-header">
              <i class="bi bi-droplet me-2"></i>Productos Utilizados
            </div>
            <div class="card-form-body">
              <div id="productos-detalle">
                ${
                  data.productos && data.productos.length > 0
                    ? data.productos
                        .map(
                          (p) => `
                    <div class="mb-2">
                      <strong>${
                        p.producto_nombre
                      }:</strong> ${Number.parseFloat(p.cantidad).toFixed(2)} ${
                            p.unidad || "kg"
                          }
                    </div>
                  `
                        )
                        .join("")
                    : '<div class="text-muted">No se registraron productos</div>'
                }
              </div>
            </div>
          </div>
        </div>
      </div>
    `;
  }

  // Función para cargar detalles del registro en el panel lateral
  function cargarDetallesRegistro(id) {
    $("#registro-detalle .detail-content").html(
      '<div class="text-center p-4"><div class="spinner-border text-primary" role="status"></div><p class="mt-2">Cargando detalles...</p></div>'
    );
    $("#registro-detalle").addClass("active");

    $.ajax({
      url: getUrl("api/registros/flotacion/obtener.php"),
      type: "GET",
      data: { id: id },
      dataType: "json",
      success: (response) => {
        if (response.success && response.data) {
          const data = response.data;
          registroSeleccionado = data;

          $("#registro-detalle .detail-header").html(`
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <h2 class="detail-title">Registro: ${data.codigo_registro}</h2>
                <p class="detail-subtitle">Fecha: ${data.fecha_formateada}</p>
              </div>
            </div>
          `);

          const resultadoEsperado = data.resultado_esperado
            ? Number.parseFloat(data.resultado_esperado).toFixed(2)
            : "0.00";
          const calificacion = data.calificacion
            ? Number.parseFloat(data.calificacion).toFixed(1)
            : "0.0";

          // Generar mensaje de estado del cálculo para el panel lateral
          let estadoMensaje = "";
          let estadoColor = "bg-success";

          switch (data.estado_calculo) {
            case "completo":
              estadoMensaje = "✓ Completo";
              estadoColor = "bg-success";
              break;
            case "completo_sin_extra":
              estadoMensaje = "✓ Completo (sin extra)";
              estadoColor = "bg-info";
              break;
            case "sin_laboratorio":
              estadoMensaje = "⚠️ Sin laboratorio";
              estadoColor = "bg-warning";
              break;
            case "falta_ley_extra":
              estadoMensaje = "⚠️ Sin ley extra";
              estadoColor = "bg-warning";
              break;
            case "parcial":
              estadoMensaje = "⚠️ Parcial";
              estadoColor = "bg-warning";
              break;
            default:
              estadoMensaje = "Desconocido";
              estadoColor = "bg-secondary";
          }

          const infoBasica = `
            <div class="card-form mb-3">
              <div class="card-form-header">
                <i class="bi bi-info-circle me-2"></i>Información Básica
              </div>
              <div class="card-form-body">
                <div class="row g-2">
                  <div class="col-md-6">
                    <div class="form-group mb-2">
                      <label class="form-label form-label-sm">N°</label>
                      <div class="form-control form-control-sm bg-light">${
                        data.id
                      }</div>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="form-group mb-2">
                      <label class="form-label form-label-sm">Código</label>
                      <div class="form-control form-control-sm bg-light">${
                        data.codigo_registro
                      }</div>
                    </div>
                  </div>
                  <div class="col-md-12">
                    <div class="form-group mb-2">
                      <label class="form-label form-label-sm">Turno</label>
                      <div class="form-control form-control-sm bg-light">${
                        data.turno_nombre
                      }</div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div class="card-form mb-3">
              <div class="card-form-header">
                <i class="bi bi-box-seam me-2"></i>Carga y Producción
              </div>
              <div class="card-form-body">
                <div class="row g-2">
                  <div class="col-md-6">
                    <div class="form-group mb-2">
                      <label class="form-label form-label-sm">Carga Promedio</label>
                      <div class="form-control form-control-sm bg-light">${Number.parseFloat(
                        data.carga_mineral_promedio
                      ).toFixed(2)} t</div>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="form-group mb-2">
                      <label class="form-label form-label-sm">Carga Extra</label>
                      <div class="form-control form-control-sm bg-light">${
                        data.carga_mineral_extra
                          ? Number.parseFloat(data.carga_mineral_extra).toFixed(
                              2
                            ) + " t"
                          : "-"
                      }</div>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="form-group mb-2">
                      <label class="form-label form-label-sm">Ley Lab.</label>
                      <div class="form-control form-control-sm bg-light">${
                        data.ley_laboratorio
                          ? Number.parseFloat(data.ley_laboratorio).toFixed(2) +
                            " g/t"
                          : "-"
                      }</div>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="form-group mb-2">
                      <label class="form-label form-label-sm">Ley Extra</label>
                      <div class="form-control form-control-sm bg-light">${
                        data.ley_inferido_metalurgista_extra
                          ? Number.parseFloat(
                              data.ley_inferido_metalurgista_extra
                            ).toFixed(2) + " g/t"
                          : "-"
                      }</div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div class="card-form mb-3">
              <div class="card-form-header">
                <i class="bi bi-calculator me-2"></i>Resultados
              </div>
              <div class="card-form-body">
                <div class="form-group mb-2">
                  <div class="form-control form-control-sm ${estadoColor} text-white fw-bold text-center">${resultadoEsperado} g</div>
                  <small class="text-muted">Resultado Esperado - ${estadoMensaje}</small>
                </div>
                <div class="form-group mb-2">
                  <div class="form-control form-control-sm bg-warning text-dark fw-bold text-center">${calificacion}%</div>
                  <small class="text-muted">Calificación</small>
                </div>
              </div>
            </div>
          `;

          let botonEditar = "";
          if (tienePermiso("registros.flotacion.editar")) {
            botonEditar = `
              <div class="d-grid gap-2 mt-3">
                <button type="button" id="btn-editar-panel" class="btn btn-warning" data-id="${data.id}">
                  <i class="bi bi-pencil me-2"></i>Editar Registro
                </button>
              </div>
            `;
          }

          $("#registro-detalle .detail-content").html(`
            ${infoBasica}
            ${botonEditar}
          `);

          $("#btn-editar-panel").on("click", function () {
            const id = $(this).data("id");
            abrirModalEditar(id);
          });
        } else {
          $("#registro-detalle .detail-content").html(`
            <div class="detail-empty">
              <div class="detail-empty-icon">
                <i class="bi bi-exclamation-triangle"></i>
              </div>
              <div class="detail-empty-text">
                Error al cargar los detalles del registro
              </div>
            </div>
          `);
        }
      },
      error: (xhr, status, error) => {
        $("#registro-detalle .detail-content").html(`
          <div class="detail-empty">
            <div class="detail-empty-icon">
              <i class="bi bi-exclamation-triangle"></i>
            </div>
            <div class="detail-empty-text">
              Error de conexión al servidor
            </div>
          </div>
        `);
        console.error("Error al obtener detalles del registro:", error);
      },
    });
  }

  // Función para abrir modal de crear registro
  function abrirModalCrear() {
    $("#form-registro")[0].reset();
    $("#registro-id").val("");
    $("#modal-registro-titulo").text("Nuevo Registro de Flotación");

    // Establecer fecha actual
    const hoy = new Date();
    const fechaFormateada = hoy.toLocaleDateString("es-ES");
    $("#registro-fecha").val(fechaFormateada);

    // Limpiar productos
    productosSeleccionados = [];
    $("#productos-container").html(`
      <div class="text-muted text-center p-3">
        <i class="bi bi-info-circle me-2"></i>
        Los productos se cargarán automáticamente
      </div>
    `);

    // Ocultar campos de laboratorio
    $("#registro-incluir-laboratorio").prop("checked", false);
    $("#grupo-codigo-muestra, #grupo-ley-laboratorio").hide();

    modalRegistro = new bootstrap.Modal(
      document.getElementById("modal-registro")
    );
    modalRegistro.show();
  }

  // Función para abrir modal de editar registro
  function abrirModalEditar(id) {
    showLoadingOverlay();

    $.ajax({
      url: getUrl("api/registros/flotacion/obtener.php"),
      type: "GET",
      data: { id: id },
      dataType: "json",
      success: (response) => {
        hideLoadingOverlay();

        if (response.success && response.data) {
          const data = response.data;

          $("#registro-id").val(data.id);
          $("#registro-fecha").val(data.fecha_formateada);
          $("#registro-turno").val(data.turno_id);
          $("#registro-carga-promedio").val(data.carga_mineral_promedio);
          $("#registro-carga-extra").val(data.carga_mineral_extra || "");
          $("#registro-codigo-muestra-extra").val(
            data.codigo_muestra_mat_extra || ""
          );
          $("#registro-ley-extra").val(
            data.ley_inferido_metalurgista_extra || ""
          );

          // Datos de laboratorio
          if (data.codigo_muestra || data.ley_laboratorio) {
            $("#registro-incluir-laboratorio").prop("checked", true);
            $("#grupo-codigo-muestra, #grupo-ley-laboratorio").show();
            $("#registro-codigo-muestra").val(data.codigo_muestra || "");
            $("#registro-ley-laboratorio").val(data.ley_laboratorio || "");
          } else {
            $("#registro-incluir-laboratorio").prop("checked", false);
            $("#grupo-codigo-muestra, #grupo-ley-laboratorio").hide();
          }

          // Cargar productos
          productosSeleccionados = [];
          $("#productos-container").html(`
            <div class="text-muted text-center p-3">
              <i class="bi bi-info-circle me-2"></i>
              Los productos se cargarán automáticamente
            </div>
          `);

          if (data.productos && data.productos.length > 0) {
            data.productos.forEach((producto) => {
              agregarProducto(producto.producto_id, producto.cantidad);
            });
          }

          $("#modal-registro-titulo").text("Editar Registro de Flotación");

          modalRegistro = new bootstrap.Modal(
            document.getElementById("modal-registro")
          );
          modalRegistro.show();
        } else {
          if (window.showErrorToast) {
            window.showErrorToast(
              response.message || "Error al obtener los datos del registro"
            );
          }
        }
      },
      error: (xhr, status, error) => {
        hideLoadingOverlay();
        if (window.showErrorToast) {
          window.showErrorToast("Error de conexión al servidor");
        }
        console.error("Error al obtener registro:", error);
      },
    });
  }

  // Función para guardar registro
  function guardarRegistro() {
    if (!validarFormulario()) {
      return;
    }

    showLoadingOverlay();

    const formData = new FormData();
    const id = $("#registro-id").val();

    if (id) {
      formData.append("id", id);
    }

    // Convertir fecha de dd/mm/yyyy a yyyy-mm-dd
    const fechaInput = $("#registro-fecha").val();
    const fechaParts = fechaInput.split("/");
    const fechaFormatted = `${fechaParts[2]}-${fechaParts[1]}-${fechaParts[0]}`;

    formData.append("fecha", fechaFormatted);
    formData.append("turno_id", $("#registro-turno").val());
    formData.append(
      "carga_mineral_promedio",
      $("#registro-carga-promedio").val()
    );
    formData.append("carga_mineral_extra", $("#registro-carga-extra").val());
    formData.append(
      "codigo_muestra_mat_extra",
      $("#registro-codigo-muestra-extra").val()
    );
    formData.append(
      "ley_inferido_metalurgista_extra",
      $("#registro-ley-extra").val()
    );

    // Datos de laboratorio
    if ($("#registro-incluir-laboratorio").is(":checked")) {
      formData.append("codigo_muestra", $("#registro-codigo-muestra").val());
      formData.append("ley_laboratorio", $("#registro-ley-laboratorio").val());
    }

    // Productos químicos
    const productos = [];
    $(".producto-item").each(function () {
      const productoId = $(this).find(".producto-select").val();
      const cantidad = $(this).find("input[type='number']").val();

      if (productoId && cantidad) {
        productos.push({
          producto_id: productoId,
          cantidad: cantidad,
        });
      }
    });

    formData.append("productos", JSON.stringify(productos));

    $.ajax({
      url: getUrl("api/registros/flotacion/guardar.php"),
      type: "POST",
      data: formData,
      processData: false,
      contentType: false,
      dataType: "json",
      success: (response) => {
        hideLoadingOverlay();

        if (response.success) {
          if (modalRegistro) {
            modalRegistro.hide();
          }

          if (window.showSuccessToast) {
            window.showSuccessToast(
              response.message || "Registro guardado correctamente"
            );
          }

          flotacionTable.ajax.reload();

          if (registroSeleccionado && registroSeleccionado.id == id) {
            $("#registro-detalle").removeClass("loaded").addClass("loading");
            setTimeout(() => {
              cargarDetallesRegistro(id);
              $("#registro-detalle").removeClass("loading").addClass("loaded");
            }, 300);
          }
        } else {
          if (window.showErrorToast) {
            window.showErrorToast(
              response.message || "Error al guardar el registro"
            );
          }
        }
      },
      error: (xhr, status, error) => {
        hideLoadingOverlay();
        if (window.showErrorToast) {
          window.showErrorToast("Error de conexión al servidor");
        }
        console.error("Error al guardar registro:", error);
      },
    });
  }

  // Función para eliminar registro
  function eliminarRegistro(id) {
    if (!confirm("¿Está seguro de que desea eliminar este registro?")) {
      return;
    }

    showLoadingOverlay();

    $.ajax({
      url: getUrl("api/registros/flotacion/eliminar.php"),
      type: "POST",
      data: { id: id },
      dataType: "json",
      success: (response) => {
        hideLoadingOverlay();

        if (response.success) {
          if (window.showSuccessToast) {
            window.showSuccessToast(
              response.message || "Registro eliminado correctamente"
            );
          }

          flotacionTable.ajax.reload();

          // Limpiar panel de detalles si el registro eliminado estaba seleccionado
          if (registroSeleccionado && registroSeleccionado.id == id) {
            $("#registro-detalle .detail-content").html(`
              <div class="detail-empty">
                <div class="detail-empty-icon">
                  <i class="bi bi-info-circle"></i>
                </div>
                <div class="detail-empty-text">
                  Seleccione un registro para ver sus detalles
                </div>
              </div>
            `);
            $("#registro-detalle").removeClass("active");
            registroSeleccionado = null;
          }
        } else {
          if (window.showErrorToast) {
            window.showErrorToast(
              response.message || "Error al eliminar el registro"
            );
          }
        }
      },
      error: (xhr, status, error) => {
        hideLoadingOverlay();
        if (window.showErrorToast) {
          window.showErrorToast("Error de conexión al servidor");
        }
        console.error("Error al eliminar registro:", error);
      },
    });
  }

  // Función para validar el formulario
  function validarFormulario() {
    const fecha = $("#registro-fecha").val();
    if (!fecha) {
      if (window.showErrorToast) {
        window.showErrorToast("La fecha es obligatoria");
      }
      $("#registro-fecha").focus();
      return false;
    }

    const turno = $("#registro-turno").val();
    if (!turno || turno === "add-new") {
      if (window.showErrorToast) {
        window.showErrorToast("Debe seleccionar un turno válido");
      }
      $("#registro-turno").focus();
      return false;
    }

    const cargaPromedio = $("#registro-carga-promedio").val();
    if (!cargaPromedio || Number.parseFloat(cargaPromedio) < 0) {
      if (window.showErrorToast) {
        window.showErrorToast(
          "La carga mineral promedio debe ser un valor válido mayor o igual a 0"
        );
      }
      $("#registro-carga-promedio").focus();
      return false;
    }

    // Validar código de muestra extra si hay carga extra
    const cargaExtra = $("#registro-carga-extra").val();
    const codigoMuestraExtra = $("#registro-codigo-muestra-extra").val();

    if (
      cargaExtra &&
      Number.parseFloat(cargaExtra) > 0 &&
      !codigoMuestraExtra
    ) {
      if (window.showErrorToast) {
        window.showErrorToast(
          "Debe ingresar el código de muestra para el material extra"
        );
      }
      $("#registro-codigo-muestra-extra").focus();
      return false;
    }

    return true;
  }

  // Aplicar filtros
  function aplicarFiltros() {
    const fechaInicio = $("#filtro-fecha-inicio").val();
    const fechaFin = $("#filtro-fecha-fin").val();
    const turno = $("#filtro-turno").val();
    const codigo = $("#filtro-codigo").val();

    filtrosActivos = {};
    if (fechaInicio) filtrosActivos.fecha_inicio = fechaInicio;
    if (fechaFin) filtrosActivos.fecha_fin = fechaFin;
    if (turno && turno !== "add-new") filtrosActivos.turno_id = turno;
    if (codigo) filtrosActivos.codigo = codigo;

    if (window.showInfoToast) {
      window.showInfoToast("Aplicando filtros...");
    }

    flotacionTable.ajax.reload();

    $("#registro-detalle .detail-content").html(`
      <div class="detail-empty">
        <div class="detail-empty-icon">
          <i class="bi bi-info-circle"></i>
        </div>
        <div class="detail-empty-text">
          Seleccione un registro para ver sus detalles
        </div>
      </div>
    `);
    $("#registro-detalle").removeClass("active");
    registroSeleccionado = null;
  }

  // Limpiar filtros
  function limpiarFiltros() {
    $("#filtro-fecha-inicio").val("");
    $("#filtro-fecha-fin").val("");
    $("#filtro-turno").val("");
    $("#filtro-codigo").val("");

    filtrosActivos = {};

    if (window.showInfoToast) {
      window.showInfoToast("Limpiando filtros...");
    }

    flotacionTable.ajax.reload();

    $("#registro-detalle .detail-content").html(`
      <div class="detail-empty">
        <div class="detail-empty-icon">
          <i class="bi bi-info-circle"></i>
        </div>
        <div class="detail-empty-text">
          Seleccione un registro para ver sus detalles
        </div>
      </div>
    `);
    $("#registro-detalle").removeClass("active");
    registroSeleccionado = null;
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
  inicializarComponentes();
  initDataTable();

  // Event Listeners
  $("#btn-nuevo-registro").on("click", () => {
    abrirModalCrear();
  });

  $("#btn-guardar-registro").on("click", () => {
    guardarRegistro();
  });

  // Event listener para el botón de ver detalles (modal)
  $(document).on("click", ".btn-ver-registro", function (e) {
    e.stopPropagation();
    const id = $(this).data("id");
    mostrarModalDetalles(id);
  });

  $(document).on("click", ".btn-editar-registro", function (e) {
    e.stopPropagation();
    const id = $(this).data("id");
    abrirModalEditar(id);
  });

  $(document).on("click", ".btn-eliminar-registro", function (e) {
    e.stopPropagation();
    const id = $(this).data("id");
    eliminarRegistro(id);
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
