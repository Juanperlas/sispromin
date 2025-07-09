/**
 * Gestión de historial general de registros
 * Funcionalidades para listar y ver detalles de todos los registros
 */

// Declaración de variables globales
const $ = window.jQuery;
const bootstrap = window.bootstrap;

// Variables globales
let historialTable;
let registroSeleccionado;
let modalVerRegistro;
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

    // Establecer fecha de hoy por defecto
    const hoy = new Date();
    const fechaFormateada = hoy.toLocaleDateString("es-ES");
    $("#filtro-fecha-inicio").val(fechaFormateada);
    $("#filtro-fecha-fin").val(fechaFormateada);

    // Aplicar filtros iniciales
    filtrosActivos = {
      fecha_inicio: fechaFormateada,
      fecha_fin: fechaFormateada,
    };
  }

  // Función para obtener el badge del tipo de registro
  function obtenerBadgeTipo(tipo) {
    const tipos = {
      mina: '<span class="tipo-badge tipo-mina">Mina</span>',
      planta: '<span class="tipo-badge tipo-planta">Planta</span>',
      amalgamacion:
        '<span class="tipo-badge tipo-amalgamacion">Amalgamación</span>',
      flotacion: '<span class="tipo-badge tipo-flotacion">Flotación</span>',
    };
    return tipos[tipo] || '<span class="tipo-badge">Desconocido</span>';
  }

  // Inicializar DataTable
  function initDataTable() {
    historialTable = $("#historial-table").DataTable({
      processing: true,
      serverSide: true,
      responsive: true,
      ajax: {
        url: getUrl("api/registros/historial/listar.php"),
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
          data: "tipo_registro",
          className: "align-middle text-center",
          render: (data) => {
            return obtenerBadgeTipo(data);
          },
        },
        {
          data: "codigo_registro",
          className: "align-middle text-center",
        },
        {
          data: "fecha_formateada",
          className: "align-middle text-center",
        },
        {
          data: "turno_nombre",
          className: "align-middle text-center",
        },
        {
          data: "creado_formateado",
          className: "align-middle text-center",
        },
        {
          data: null,
          orderable: false,
          className: "text-center align-middle",
          render: (data) => {
            let acciones = '<div class="btn-group btn-group-sm">';
            acciones += `<button type="button" class="btn-accion btn-ver-registro" data-id="${data.id}" data-tipo="${data.tipo_registro}" title="Ver detalles"><i class="bi bi-eye"></i></button>`;

            // Solo mostrar botón de editar si tiene permisos para ese tipo específico
            if (tienePermisoEditar(data.tipo_registro)) {
              acciones += `<button type="button" class="btn-accion btn-editar-registro" data-id="${data.id}" data-tipo="${data.tipo_registro}" title="Editar"><i class="bi bi-pencil"></i></button>`;
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
            columns: [0, 1, 2, 3, 4, 5],
          },
        },
        {
          extend: "excel",
          text: '<i class="bi bi-file-earmark-excel"></i> Excel',
          className: "btn btn-sm",
          exportOptions: {
            columns: [0, 1, 2, 3, 4, 5],
          },
        },
        {
          extend: "pdf",
          text: '<i class="bi bi-file-earmark-pdf"></i> PDF',
          className: "btn btn-sm",
          exportOptions: {
            columns: [0, 1, 2, 3, 4, 5],
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
              text: "HISTORIAL GENERAL DE REGISTROS",
              style: "header",
              alignment: "center",
              margin: [0, 15, 0, 15],
            });

            const tableIndex = doc.content.findIndex((item) => item.table);
            if (tableIndex !== -1) {
              doc.content[tableIndex].table.widths = [8, 12, 15, 12, 15, 15];

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
                    cell.alignment = "center";
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
              title: "Historial General de Registros",
              author: "SISPROMIN",
              subject: "Listado de Todos los Registros",
            };
          },
          filename:
            "Historial_General_" + new Date().toISOString().split("T")[0],
          orientation: "landscape",
        },
        {
          extend: "print",
          text: '<i class="bi bi-printer"></i> Imprimir',
          className: "btn btn-sm",
          exportOptions: {
            columns: [0, 1, 2, 3, 4, 5],
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
        $("#historial-table tbody").on("click", "tr", function () {
          const data = historialTable.row(this).data();
          if (data) {
            $("#historial-table tbody tr").removeClass("selected");
            $(this).addClass("selected");

            $("#registro-detalle").removeClass("loaded").addClass("loading");

            setTimeout(() => {
              cargarDetallesRegistro(data.id, data.tipo_registro);
              $("#registro-detalle").removeClass("loading").addClass("loaded");
            }, 300);
          }
        });

        // Actualizar contador de registros
        actualizarContadorRegistros();
      },
      drawCallback: () => {
        if (window.hideLoading) {
          window.hideLoading();
        }
        // Actualizar contador después de cada draw
        actualizarContadorRegistros();
      },
      preDrawCallback: () => {
        if (window.showLoading) {
          window.showLoading();
        }
      },
    });
  }

  // Función para actualizar el contador de registros
  function actualizarContadorRegistros() {
    const info = historialTable.page.info();
    $("#total-registros").text(`${info.recordsDisplay} registros`);
  }

  // Función para mostrar modal con detalles del registro
  function mostrarModalDetalles(id, tipo) {
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
      url: getUrl("api/registros/historial/obtener.php"),
      type: "GET",
      data: { id: id, tipo: tipo },
      dataType: "json",
      success: (response) => {
        if (response.success && response.data) {
          const data = response.data;
          registroSeleccionado = data;

          $("#modal-ver-registro-titulo").html(`
            <i class="bi bi-eye me-2"></i>Detalles del Registro: ${
              data.codigo_registro
            } (${tipo.toUpperCase()})
          `);

          const contenidoModal = generarContenidoModalDetalle(data, tipo);
          $("#modal-ver-registro-body").html(contenidoModal);

          // Mostrar botón de editar si tiene permisos
          if (tienePermisoEditar(tipo)) {
            $("#btn-editar-desde-modal")
              .show()
              .off("click")
              .on("click", () => {
                modalVerRegistro.hide();
                setTimeout(() => {
                  abrirModuloEditar(data.id, tipo);
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
  function generarContenidoModalDetalle(data, tipo) {
    let contenidoEspecifico = "";

    switch (tipo) {
      case "mina":
        contenidoEspecifico = `
          <div class="col-md-6">
            <div class="card-form mb-3">
              <div class="card-form-header">
                <i class="bi bi-geo-alt me-2"></i>Datos de Mina
              </div>
              <div class="card-form-body">
                <div class="row g-3">
                  <div class="col-md-6">
                    <div class="form-group">
                      <label class="form-label form-label-sm fw-bold">Frente</label>
                      <div class="form-control form-control-sm bg-light">${
                        data.frente_nombre || "-"
                      }</div>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="form-group">
                      <label class="form-label form-label-sm fw-bold">Material Extraído</label>
                      <div class="form-control form-control-sm bg-light">${
                        data.material_extraido
                          ? Number.parseFloat(data.material_extraido).toFixed(
                              2
                            ) + " t"
                          : "-"
                      }</div>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="form-group">
                      <label class="form-label form-label-sm fw-bold">Desmonte</label>
                      <div class="form-control form-control-sm bg-light">${
                        data.desmonte
                          ? Number.parseFloat(data.desmonte).toFixed(2) + " t"
                          : "-"
                      }</div>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="form-group">
                      <label class="form-label form-label-sm fw-bold">Ley Inferido Geólogo</label>
                      <div class="form-control form-control-sm bg-light">${
                        data.ley_inferido_geologo
                          ? Number.parseFloat(
                              data.ley_inferido_geologo
                            ).toFixed(2) + " g/t"
                          : "-"
                      }</div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        `;
        break;

      case "planta":
        contenidoEspecifico = `
          <div class="col-md-6">
            <div class="card-form mb-3">
              <div class="card-form-header">
                <i class="bi bi-gear me-2"></i>Datos de Planta
              </div>
              <div class="card-form-body">
                <div class="row g-3">
                  <div class="col-md-6">
                    <div class="form-group">
                      <label class="form-label form-label-sm fw-bold">Línea</label>
                      <div class="form-control form-control-sm bg-light">${
                        data.linea_nombre || "-"
                      }</div>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="form-group">
                      <label class="form-label form-label-sm fw-bold">Material Procesado</label>
                      <div class="form-control form-control-sm bg-light">${
                        data.material_procesado
                          ? Number.parseFloat(data.material_procesado).toFixed(
                              2
                            ) + " t"
                          : "-"
                      }</div>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="form-group">
                      <label class="form-label form-label-sm fw-bold">Concentrado</label>
                      <div class="form-control form-control-sm bg-light">${
                        data.concentrado_nombre || "-"
                      }</div>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="form-group">
                      <label class="form-label form-label-sm fw-bold">Producción</label>
                      <div class="form-control form-control-sm bg-light">${
                        data.produccion_cantidad
                          ? Number.parseFloat(data.produccion_cantidad).toFixed(
                              2
                            ) + " t"
                          : "-"
                      }</div>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="form-group">
                      <label class="form-label form-label-sm fw-bold">Peso Aproximado</label>
                      <div class="form-control form-control-sm bg-light">${
                        data.peso_aproximado_kg
                          ? Number.parseFloat(data.peso_aproximado_kg).toFixed(
                              2
                            ) + " kg"
                          : "-"
                      }</div>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="form-group">
                      <label class="form-label form-label-sm fw-bold">Ley Inferido Metalurgista</label>
                      <div class="form-control form-control-sm bg-light">${
                        data.ley_inferido_metalurgista
                          ? Number.parseFloat(
                              data.ley_inferido_metalurgista
                            ).toFixed(2) + " g/t"
                          : "-"
                      }</div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        `;
        break;

      case "amalgamacion":
        contenidoEspecifico = `
          <div class="col-md-6">
            <div class="card-form mb-3">
              <div class="card-form-header">
                <i class="bi bi-droplet-half me-2"></i>Datos de Amalgamación
              </div>
              <div class="card-form-body">
                <div class="row g-3">
                  <div class="col-md-6">
                    <div class="form-group">
                      <label class="form-label form-label-sm fw-bold">Línea</label>
                      <div class="form-control form-control-sm bg-light">${
                        data.linea_nombre || "-"
                      }</div>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="form-group">
                      <label class="form-label form-label-sm fw-bold">Amalgamador</label>
                      <div class="form-control form-control-sm bg-light">${
                        data.amalgamador_nombre || "-"
                      }</div>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="form-group">
                      <label class="form-label form-label-sm fw-bold">Cantidad Carga</label>
                      <div class="form-control form-control-sm bg-light">${
                        data.cantidad_carga_concentrados
                          ? Number.parseFloat(
                              data.cantidad_carga_concentrados
                            ).toFixed(1) + " t"
                          : "-"
                      }</div>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="form-group">
                      <label class="form-label form-label-sm fw-bold">Carga Mercurio</label>
                      <div class="form-control form-control-sm bg-light">${
                        data.carga_mercurio_kg
                          ? Number.parseFloat(data.carga_mercurio_kg).toFixed(
                              2
                            ) + " kg"
                          : "-"
                      }</div>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="form-group">
                      <label class="form-label form-label-sm fw-bold">Amalgamación</label>
                      <div class="form-control form-control-sm bg-light">${
                        data.amalgamacion_gramos
                          ? Number.parseFloat(data.amalgamacion_gramos).toFixed(
                              2
                            ) + " g"
                          : "-"
                      }</div>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="form-group">
                      <label class="form-label form-label-sm fw-bold">Mercurio Recuperado</label>
                      <div class="form-control form-control-sm bg-light">${
                        data.mercurio_recuperado_kg
                          ? Number.parseFloat(
                              data.mercurio_recuperado_kg
                            ).toFixed(2) + " kg"
                          : "-"
                      }</div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        `;
        break;

      case "flotacion":
        contenidoEspecifico = `
          <div class="col-md-6">
            <div class="card-form mb-3">
              <div class="card-form-header">
                <i class="bi bi-water me-2"></i>Datos de Flotación
              </div>
              <div class="card-form-body">
                <div class="row g-3">
                  <div class="col-md-6">
                    <div class="form-group">
                      <label class="form-label form-label-sm fw-bold">Carga Promedio</label>
                      <div class="form-control form-control-sm bg-light">${
                        data.carga_mineral_promedio
                          ? Number.parseFloat(
                              data.carga_mineral_promedio
                            ).toFixed(2) + " t"
                          : "-"
                      }</div>
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
                  <div class="col-md-12">
                    <div class="form-group">
                      <label class="form-label form-label-sm fw-bold">Código Muestra Extra</label>
                      <div class="form-control form-control-sm bg-light">${
                        data.codigo_muestra_mat_extra || "-"
                      }</div>
                    </div>
                  </div>
                  <div class="col-md-6">
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
                  <div class="col-md-6">
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
        `;
        break;
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
                    <label class="form-label form-label-sm fw-bold">Tipo</label>
                    <div class="form-control form-control-sm bg-light">${tipo.toUpperCase()}</div>
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
                <div class="col-md-12">
                  <div class="form-group">
                    <label class="form-label form-label-sm fw-bold">Turno</label>
                    <div class="form-control form-control-sm bg-light">${
                      data.turno_nombre
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
        ${contenidoEspecifico}
      </div>
    `;
  }

  // Función para cargar detalles del registro en el panel lateral
  function cargarDetallesRegistro(id, tipo) {
    $("#registro-detalle .detail-content").html(
      '<div class="text-center p-4"><div class="spinner-border text-primary" role="status"></div><p class="mt-2">Cargando detalles...</p></div>'
    );
    $("#registro-detalle").addClass("active");

    $.ajax({
      url: getUrl("api/registros/historial/obtener.php"),
      type: "GET",
      data: { id: id, tipo: tipo },
      dataType: "json",
      success: (response) => {
        if (response.success && response.data) {
          const data = response.data;
          registroSeleccionado = data;

          $("#registro-detalle .detail-header").html(`
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <h2 class="detail-title">${obtenerBadgeTipo(tipo)} ${
            data.codigo_registro
          }</h2>
                <p class="detail-subtitle">Fecha: ${data.fecha_formateada}</p>
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
                  <div class="col-md-12">
                    <div class="form-group mb-2">
                      <label class="form-label form-label-sm">Creado</label>
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
          if (tienePermisoEditar(tipo)) {
            botonEditar = `
              <div class="d-grid gap-2 mt-3">
                <button type="button" id="btn-editar-panel" class="btn btn-warning" data-id="${data.id}" data-tipo="${tipo}">
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
            const tipo = $(this).data("tipo");
            abrirModuloEditar(id, tipo);
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

  // Función para abrir el módulo de edición correspondiente
  function abrirModuloEditar(id, tipo) {
    const urls = {
      mina: `modulos/registros/mina/index.php?edit=${id}`,
      planta: `modulos/registros/planta/index.php?edit=${id}`,
      amalgamacion: `modulos/registros/amalgamacion/index.php?edit=${id}`,
      flotacion: `modulos/registros/flotacion/index.php?edit=${id}`,
    };

    if (urls[tipo]) {
      window.open(getBaseUrl() + urls[tipo], "_blank");
    } else {
      if (window.showErrorToast) {
        window.showErrorToast("Tipo de registro no válido");
      }
    }
  }

  // Aplicar filtros
  function aplicarFiltros() {
    const fechaInicio = $("#filtro-fecha-inicio").val();
    const fechaFin = $("#filtro-fecha-fin").val();
    const tipoRegistro = $("#filtro-tipo-registro").val();
    const codigo = $("#filtro-codigo").val();

    filtrosActivos = {};
    if (fechaInicio) filtrosActivos.fecha_inicio = fechaInicio;
    if (fechaFin) filtrosActivos.fecha_fin = fechaFin;
    if (tipoRegistro) filtrosActivos.tipo_registro = tipoRegistro;
    if (codigo) filtrosActivos.codigo = codigo;

    if (window.showInfoToast) {
      window.showInfoToast("Aplicando filtros...");
    }

    historialTable.ajax.reload();

    $("#registro-detalle .detail-content").html(`
      <div class="detail-empty">
        <div class="detail-empty-icon">
          <i class="bi bi-clock-history"></i>
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
    $("#filtro-tipo-registro").val("");
    $("#filtro-codigo").val("");

    filtrosActivos = {};

    if (window.showInfoToast) {
      window.showInfoToast("Limpiando filtros...");
    }

    historialTable.ajax.reload();

    $("#registro-detalle .detail-content").html(`
      <div class="detail-empty">
        <div class="detail-empty-icon">
          <i class="bi bi-clock-history"></i>
        </div>
        <div class="detail-empty-text">
          Seleccione un registro para ver sus detalles
        </div>
      </div>
    `);
    $("#registro-detalle").removeClass("active");
    registroSeleccionado = null;
  }

  // Filtrar por hoy
  function filtrarHoy() {
    const hoy = new Date();
    const fechaFormateada = hoy.toLocaleDateString("es-ES");

    $("#filtro-fecha-inicio").val(fechaFormateada);
    $("#filtro-fecha-fin").val(fechaFormateada);
    $("#filtro-tipo-registro").val("");
    $("#filtro-codigo").val("");

    aplicarFiltros();
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

  // Verificar si el usuario tiene permiso para editar un tipo específico
  function tienePermisoEditar(tipo) {
    const permisos = {
      mina: "registros.produccion_mina.editar",
      planta: "registros.planta.editar",
      amalgamacion: "registros.amalgamacion.editar",
      flotacion: "registros.flotacion.editar",
    };

    if (typeof window.tienePermiso === "function" && permisos[tipo]) {
      return window.tienePermiso(permisos[tipo]);
    }
    return false;
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
  // Event listener para el botón de ver detalles (modal)
  $(document).on("click", ".btn-ver-registro", function (e) {
    e.stopPropagation();
    const id = $(this).data("id");
    const tipo = $(this).data("tipo");
    mostrarModalDetalles(id, tipo);
  });

  $(document).on("click", ".btn-editar-registro", function (e) {
    e.stopPropagation();
    const id = $(this).data("id");
    const tipo = $(this).data("tipo");
    abrirModuloEditar(id, tipo);
  });

  $("#btn-aplicar-filtros").on("click", () => {
    aplicarFiltros();
  });

  $("#btn-limpiar-filtros").on("click", () => {
    limpiarFiltros();
  });

  $("#btn-hoy").on("click", () => {
    filtrarHoy();
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
