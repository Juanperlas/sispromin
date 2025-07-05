/**
 * Gestión de registros de amalgamación
 * Funcionalidades para listar, crear, editar y eliminar registros de amalgamación
 */

// Declaración de variables globales
const $ = window.jQuery;
const bootstrap = window.bootstrap;

// Variables globales
let amalgamacionTable;
let registroSeleccionado;
let modalRegistro;
let modalVerRegistro;
let filtrosActivos = {};
let turnos = [];
let lineas = [];
let amalgamadores = [];
let tiposCarga = [];

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

    // Cargar datos iniciales
    cargarTurnos();
    cargarLineas();
    cargarAmalgamadores();
    cargarTiposCarga();
  }

  // Cargar turnos
  function cargarTurnos() {
    $.ajax({
      url: getUrl("api/registros/amalgamacion/obtener_turnos.php"),
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

  // Cargar líneas
  function cargarLineas() {
    $.ajax({
      url: getUrl("api/registros/amalgamacion/obtener_lineas.php"),
      type: "GET",
      dataType: "json",
      success: (response) => {
        const selectLinea = $("#registro-linea, #filtro-linea");

        // Limpiar opciones existentes (excepto la primera)
        selectLinea.find("option:not(:first)").remove();

        if (response.success && response.data && response.data.length > 0) {
          lineas = response.data;

          // Agregar nuevas opciones
          lineas.forEach((linea) => {
            selectLinea.append(
              `<option value="${linea.id}">${linea.nombre} (${linea.codigo})</option>`
            );
          });
        } else {
          // No hay datos - mostrar opción para agregar
          selectLinea.append(
            `<option value="" disabled>No hay líneas disponibles</option>`
          );
          selectLinea.append(
            `<option value="add-new" style="color: #007bff; font-weight: bold;">+ Agregar nueva línea</option>`
          );

          if (window.showWarningToast) {
            window.showWarningToast(
              "No hay líneas configuradas. Configure las líneas primero."
            );
          }
        }
      },
      error: (xhr, status, error) => {
        console.error("Error al cargar líneas:", error);

        const selectLinea = $("#registro-linea, #filtro-linea");
        selectLinea.find("option:not(:first)").remove();
        selectLinea.append(
          `<option value="" disabled>Error al cargar líneas</option>`
        );
        selectLinea.append(
          `<option value="add-new" style="color: #007bff; font-weight: bold;">+ Agregar nueva línea</option>`
        );

        if (window.showErrorToast) {
          window.showErrorToast("Error al cargar las líneas: " + error);
        }
      },
    });
  }

  // Cargar amalgamadores
  function cargarAmalgamadores() {
    $.ajax({
      url: getUrl("api/registros/amalgamacion/obtener_amalgamadores.php"),
      type: "GET",
      dataType: "json",
      success: (response) => {
        const selectAmalgamador = $(
          "#registro-amalgamador, #filtro-amalgamador"
        );

        // Limpiar opciones existentes (excepto la primera)
        selectAmalgamador.find("option:not(:first)").remove();

        if (response.success && response.data && response.data.length > 0) {
          amalgamadores = response.data;

          // Agregar nuevas opciones
          amalgamadores.forEach((amalgamador) => {
            selectAmalgamador.append(
              `<option value="${amalgamador.id}">${amalgamador.nombre} (${amalgamador.codigo})</option>`
            );
          });
        } else {
          // No hay datos - mostrar opción para agregar
          selectAmalgamador.append(
            `<option value="" disabled>No hay amalgamadores disponibles</option>`
          );
          selectAmalgamador.append(
            `<option value="add-new" style="color: #007bff; font-weight: bold;">+ Agregar nuevo amalgamador</option>`
          );

          if (window.showWarningToast) {
            window.showWarningToast(
              "No hay amalgamadores configurados. Configure los amalgamadores primero."
            );
          }
        }
      },
      error: (xhr, status, error) => {
        console.error("Error al cargar amalgamadores:", error);

        const selectAmalgamador = $(
          "#registro-amalgamador, #filtro-amalgamador"
        );
        selectAmalgamador.find("option:not(:first)").remove();
        selectAmalgamador.append(
          `<option value="" disabled>Error al cargar amalgamadores</option>`
        );
        selectAmalgamador.append(
          `<option value="add-new" style="color: #007bff; font-weight: bold;">+ Agregar nuevo amalgamador</option>`
        );

        if (window.showErrorToast) {
          window.showErrorToast("Error al cargar los amalgamadores: " + error);
        }
      },
    });
  }

  // Cargar tipos de carga
  function cargarTiposCarga() {
    $.ajax({
      url: getUrl("api/registros/amalgamacion/obtener_cargas.php"),
      type: "GET",
      dataType: "json",
      success: (response) => {
        const selectCarga = $("#registro-tipo-carga");

        // Limpiar opciones existentes (excepto la primera)
        selectCarga.find("option:not(:first)").remove();

        if (response.success && response.data && response.data.length > 0) {
          tiposCarga = response.data;

          // Agregar nuevas opciones
          tiposCarga.forEach((carga) => {
            selectCarga.append(
              `<option value="${carga.id}">${carga.nombre} (${carga.codigo})</option>`
            );
          });
        } else {
          // No hay datos - mostrar opción para agregar
          selectCarga.append(
            `<option value="" disabled>No hay tipos de carga disponibles</option>`
          );
          selectCarga.append(
            `<option value="add-new" style="color: #007bff; font-weight: bold;">+ Agregar nuevo tipo de carga</option>`
          );

          if (window.showWarningToast) {
            window.showWarningToast(
              "No hay tipos de carga configurados. Configure los tipos de carga primero."
            );
          }
        }
      },
      error: (xhr, status, error) => {
        console.error("Error al cargar tipos de carga:", error);

        const selectCarga = $("#registro-tipo-carga");
        selectCarga.find("option:not(:first)").remove();
        selectCarga.append(
          `<option value="" disabled>Error al cargar tipos de carga</option>`
        );
        selectCarga.append(
          `<option value="add-new" style="color: #007bff; font-weight: bold;">+ Agregar nuevo tipo de carga</option>`
        );

        if (window.showErrorToast) {
          window.showErrorToast("Error al cargar los tipos de carga: " + error);
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
          getBaseUrl() + "modulos/controles/amalgamacion/turnos/index.php",
          "_blank"
        );
      }
      $(this).val(""); // Resetear selección
    }
  });

  // Manejar selección de "agregar nuevo" en líneas
  $(document).on("change", "#registro-linea, #filtro-linea", function () {
    if ($(this).val() === "add-new") {
      if (
        confirm("¿Desea ir al módulo de líneas para agregar una nueva línea?")
      ) {
        window.open(
          getBaseUrl() + "modulos/controles/amalgamacion/lineas/index.php",
          "_blank"
        );
      }
      $(this).val(""); // Resetear selección
    }
  });

  // Manejar selección de "agregar nuevo" en amalgamadores
  $(document).on(
    "change",
    "#registro-amalgamador, #filtro-amalgamador",
    function () {
      if ($(this).val() === "add-new") {
        if (
          confirm(
            "¿Desea ir al módulo de amalgamadores para agregar un nuevo amalgamador?"
          )
        ) {
          window.open(
            getBaseUrl() +
              "modulos/controles/amalgamacion/amalgamadores/index.php",
            "_blank"
          );
        }
        $(this).val(""); // Resetear selección
      }
    }
  );

  // Manejar selección de "agregar nuevo" en tipos de carga
  $(document).on("change", "#registro-tipo-carga", function () {
    if ($(this).val() === "add-new") {
      if (
        confirm(
          "¿Desea ir al módulo de tipos de carga para agregar un nuevo tipo?"
        )
      ) {
        window.open(
          getBaseUrl() + "modulos/controles/amalgamacion/cargas/index.php",
          "_blank"
        );
      }
      $(this).val(""); // Resetear selección
    }
  });

  // Inicializar DataTable
  function initDataTable() {
    amalgamacionTable = $("#amalgamacion-table").DataTable({
      processing: true,
      serverSide: true,
      responsive: true,
      ajax: {
        url: getUrl("api/registros/amalgamacion/listar.php"),
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
          data: "linea_nombre",
          className: "align-middle text-center",
        },
        {
          data: "amalgamador_nombre",
          className: "align-middle text-center",
        },
        {
          data: "cantidad_carga_concentrados",
          className: "align-middle text-center",
          render: (data) => {
            return data ? Number.parseFloat(data).toFixed(1) + " kg" : "0.0 kg";
          },
        },
        {
          data: "resultado_refogado",
          className: "align-middle text-center valor-calculado",
          render: (data) => {
            return data ? Number.parseFloat(data).toFixed(2) + " g" : "0.00 g";
          },
        },
        {
          data: "porcentaje_recuperacion",
          className: "align-middle text-center",
          render: (data) => {
            const porcentaje = data ? Number.parseFloat(data) : 0;
            let colorClass = "bg-danger";

            if (porcentaje > 85) {
              colorClass = "bg-success";
            } else if (porcentaje > 70) {
              colorClass = "bg-warning";
            }

            return `<span class="badge ${colorClass}">${porcentaje.toFixed(
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

            if (tienePermiso("registros.amalgamacion.editar")) {
              acciones += `<button type="button" class="btn-accion btn-editar-registro" data-id="${data.id}" title="Editar"><i class="bi bi-pencil"></i></button>`;
            }

            if (tienePermiso("registros.amalgamacion.eliminar")) {
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
            columns: [0, 1, 2, 3, 4, 5, 6, 7, 8],
          },
        },
        {
          extend: "excel",
          text: '<i class="bi bi-file-earmark-excel"></i> Excel',
          className: "btn btn-sm",
          exportOptions: {
            columns: [0, 1, 2, 3, 4, 5, 6, 7, 8],
          },
        },
        {
          extend: "pdf",
          text: '<i class="bi bi-file-earmark-pdf"></i> PDF',
          className: "btn btn-sm",
          exportOptions: {
            columns: [0, 1, 2, 3, 4, 5, 6, 7, 8],
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
              text: "REPORTE DE AMALGAMACIÓN",
              style: "header",
              alignment: "center",
              margin: [0, 15, 0, 15],
            });

            const tableIndex = doc.content.findIndex((item) => item.table);
            if (tableIndex !== -1) {
              doc.content[tableIndex].table.widths = [
                6, 12, 8, 8, 8, 10, 8, 10, 8,
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
                    if (j === 1 || j === 2 || j === 3 || j === 4 || j === 5) {
                      cell.alignment = "center";
                    } else if (j >= 6) {
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
              title: "Reporte de Amalgamación",
              author: "SISPROMIN",
              subject: "Listado de Registros de Amalgamación",
            };
          },
          filename:
            "Reporte_Amalgamacion_" + new Date().toISOString().split("T")[0],
          orientation: "landscape",
        },
        {
          extend: "print",
          text: '<i class="bi bi-printer"></i> Imprimir',
          className: "btn btn-sm",
          exportOptions: {
            columns: [0, 1, 2, 3, 4, 5, 6, 7, 8],
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
        $("#amalgamacion-table tbody").on("click", "tr", function () {
          const data = amalgamacionTable.row(this).data();
          if (data) {
            $("#amalgamacion-table tbody tr").removeClass("selected");
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
      url: getUrl("api/registros/amalgamacion/obtener.php"),
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
          if (tienePermiso("registros.amalgamacion.editar")) {
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
    const resultadoRefogado = data.resultado_refogado
      ? Number.parseFloat(data.resultado_refogado).toFixed(2)
      : "0.00";
    const porcentajeRecuperacion = data.porcentaje_recuperacion
      ? Number.parseFloat(data.porcentaje_recuperacion).toFixed(1)
      : "0.0";

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
                <div class="col-md-6">
                  <div class="form-group">
                    <label class="form-label form-label-sm fw-bold">Línea</label>
                    <div class="form-control form-control-sm bg-light">${
                      data.linea_nombre
                    }</div>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group">
                    <label class="form-label form-label-sm fw-bold">Amalgamador</label>
                    <div class="form-control form-control-sm bg-light">${
                      data.amalgamador_nombre
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
                    <label class="form-label form-label-sm fw-bold">Cantidad Carga</label>
                    <div class="form-control form-control-sm bg-light">${Number.parseFloat(
                      data.cantidad_carga_concentrados
                    ).toFixed(1)} Kg</div>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group">
                    <label class="form-label form-label-sm fw-bold">Tipo de Carga</label>
                    <div class="form-control form-control-sm bg-light">${
                      data.carga_nombre
                    }</div>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group">
                    <label class="form-label form-label-sm fw-bold">Mercurio</label>
                    <div class="form-control form-control-sm bg-light">${Number.parseFloat(
                      data.carga_mercurio_kg
                    ).toFixed(2)} Kg</div>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group">
                    <label class="form-label form-label-sm fw-bold">Amalgamación</label>
                    <div class="form-control form-control-sm bg-light">${Number.parseFloat(
                      data.amalgamacion_gramos
                    ).toFixed(2)} g</div>
                  </div>
                </div>
                <div class="col-md-12">
                  <div class="form-group">
                    <label class="form-label form-label-sm fw-bold">Factor Conversión Au</label>
                    <div class="form-control form-control-sm bg-light">${Number.parseFloat(
                      data.factor_conversion_amalg_au
                    ).toFixed(3)}</div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-md-6">
          <div class="card-form mb-3">
            <div class="card-form-header">
              <i class="bi bi-flask me-2"></i>Productos Químicos
            </div>
            <div class="card-form-body">
              <div class="row g-3">
                <div class="col-md-6">
                  <div class="form-group">
                    <label class="form-label form-label-sm fw-bold">Soda Cáustica</label>
                    <div class="form-control form-control-sm bg-light">${
                      data.soda_caustica_kg
                        ? Number.parseFloat(data.soda_caustica_kg).toFixed(2) +
                          " Kg"
                        : "-"
                    }</div>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group">
                    <label class="form-label form-label-sm fw-bold">Detergente</label>
                    <div class="form-control form-control-sm bg-light">${
                      data.detergente_kg
                        ? Number.parseFloat(data.detergente_kg).toFixed(2) +
                          " Kg"
                        : "-"
                    }</div>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group">
                    <label class="form-label form-label-sm fw-bold">Cal</label>
                    <div class="form-control form-control-sm bg-light">${
                      data.cal_kg
                        ? Number.parseFloat(data.cal_kg).toFixed(2) + " Kg"
                        : "-"
                    }</div>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group">
                    <label class="form-label form-label-sm fw-bold">Lejía</label>
                    <div class="form-control form-control-sm bg-light">${
                      data.lejia_litros
                        ? Number.parseFloat(data.lejia_litros).toFixed(2) + " L"
                        : "-"
                    }</div>
                  </div>
                </div>
                <div class="col-md-12">
                  <div class="form-group">
                    <label class="form-label form-label-sm fw-bold">Mercurio Recuperado</label>
                    <div class="form-control form-control-sm bg-light">${
                      data.mercurio_recuperado_kg
                        ? Number.parseFloat(
                            data.mercurio_recuperado_kg
                          ).toFixed(2) + " Kg"
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
                    <label class="form-label form-label-sm fw-bold">Resultado Refogado</label>
                    <div class="form-control form-control-sm bg-success text-white fw-bold">${resultadoRefogado} g</div>
                    <small class="text-muted">Amalgamación ÷ Factor conversión</small>
                  </div>
                </div>
                <div class="col-md-12">
                  <div class="form-group">
                    <label class="form-label form-label-sm fw-bold">% Recuperación</label>
                    <div class="form-control form-control-sm bg-warning text-dark fw-bold">${porcentajeRecuperacion}%</div>
                    <small class="text-muted">Cálculo pendiente de definir</small>
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
  }

  // Función para cargar detalles del registro en el panel lateral
  function cargarDetallesRegistro(id) {
    $("#registro-detalle .detail-content").html(
      '<div class="text-center p-4"><div class="spinner-border text-primary" role="status"></div><p class="mt-2">Cargando detalles...</p></div>'
    );
    $("#registro-detalle").addClass("active");

    $.ajax({
      url: getUrl("api/registros/amalgamacion/obtener.php"),
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

          const resultadoRefogado = data.resultado_refogado
            ? Number.parseFloat(data.resultado_refogado).toFixed(2)
            : "0.00";
          const porcentajeRecuperacion = data.porcentaje_recuperacion
            ? Number.parseFloat(data.porcentaje_recuperacion).toFixed(1)
            : "0.0";

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
                  <div class="col-md-6">
                    <div class="form-group mb-2">
                      <label class="form-label form-label-sm">Turno</label>
                      <div class="form-control form-control-sm bg-light">${
                        data.turno_nombre
                      }</div>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="form-group mb-2">
                      <label class="form-label form-label-sm">Línea</label>
                      <div class="form-control form-control-sm bg-light">${
                        data.linea_nombre
                      }</div>
                    </div>
                  </div>
                  <div class="col-md-12">
                    <div class="form-group mb-2">
                      <label class="form-label form-label-sm">Amalgamador</label>
                      <div class="form-control form-control-sm bg-light">${
                        data.amalgamador_nombre
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
                      <label class="form-label form-label-sm">Cantidad Carga</label>
                      <div class="form-control form-control-sm bg-light">${Number.parseFloat(
                        data.cantidad_carga_concentrados
                      ).toFixed(1)} Kg</div>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="form-group mb-2">
                      <label class="form-label form-label-sm">Tipo Carga</label>
                      <div class="form-control form-control-sm bg-light">${
                        data.carga_nombre
                      }</div>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="form-group mb-2">
                      <label class="form-label form-label-sm">Mercurio</label>
                      <div class="form-control form-control-sm bg-light">${Number.parseFloat(
                        data.carga_mercurio_kg
                      ).toFixed(2)} Kg</div>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="form-group mb-2">
                      <label class="form-label form-label-sm">Amalgamación</label>
                      <div class="form-control form-control-sm bg-light">${Number.parseFloat(
                        data.amalgamacion_gramos
                      ).toFixed(2)} g</div>
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
                  <div class="form-control form-control-sm bg-success text-white fw-bold text-center">${resultadoRefogado} g</div>
                  <small class="text-muted">Resultado Refogado</small>
                </div>
                <div class="form-group mb-2">
                  <div class="form-control form-control-sm bg-warning text-dark fw-bold text-center">${porcentajeRecuperacion}%</div>
                  <small class="text-muted">% Recuperación</small>
                </div>
              </div>
            </div>
          `;

          let botonEditar = "";
          if (tienePermiso("registros.amalgamacion.editar")) {
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
    $("#modal-registro-titulo").text("Nuevo Registro de Amalgamación");

    // Establecer fecha actual
    const hoy = new Date();
    const fechaFormateada = hoy.toLocaleDateString("es-ES");
    $("#registro-fecha").val(fechaFormateada);

    // Establecer factor de conversión por defecto
    $("#registro-factor-conversion").val("3.3");

    modalRegistro = new bootstrap.Modal(
      document.getElementById("modal-registro")
    );
    modalRegistro.show();
  }

  // Función para abrir modal de editar registro
  function abrirModalEditar(id) {
    showLoadingOverlay();

    $.ajax({
      url: getUrl("api/registros/amalgamacion/obtener.php"),
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
          $("#registro-linea").val(data.linea_id);
          $("#registro-amalgamador").val(data.amalgamador_id);
          $("#registro-cantidad-carga").val(data.cantidad_carga_concentrados);
          $("#registro-tipo-carga").val(data.carga_id);
          $("#registro-mercurio").val(data.carga_mercurio_kg);
          $("#registro-amalgamacion").val(data.amalgamacion_gramos);
          $("#registro-soda-caustica").val(data.soda_caustica_kg || "");
          $("#registro-detergente").val(data.detergente_kg || "");
          $("#registro-cal").val(data.cal_kg || "");
          $("#registro-lejia").val(data.lejia_litros || "");
          $("#registro-mercurio-recuperado").val(
            data.mercurio_recuperado_kg || ""
          );
          $("#registro-factor-conversion").val(
            data.factor_conversion_amalg_au || "3.3"
          );

          $("#modal-registro-titulo").text("Editar Registro de Amalgamación");

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
    formData.append("linea_id", $("#registro-linea").val());
    formData.append("amalgamador_id", $("#registro-amalgamador").val());
    formData.append(
      "cantidad_carga_concentrados",
      $("#registro-cantidad-carga").val()
    );
    formData.append("carga_id", $("#registro-tipo-carga").val());
    formData.append("carga_mercurio_kg", $("#registro-mercurio").val());
    formData.append("amalgamacion_gramos", $("#registro-amalgamacion").val());
    formData.append("soda_caustica_kg", $("#registro-soda-caustica").val());
    formData.append("detergente_kg", $("#registro-detergente").val());
    formData.append("cal_kg", $("#registro-cal").val());
    formData.append("lejia_litros", $("#registro-lejia").val());
    formData.append(
      "mercurio_recuperado_kg",
      $("#registro-mercurio-recuperado").val()
    );
    formData.append(
      "factor_conversion_amalg_au",
      $("#registro-factor-conversion").val()
    );

    $.ajax({
      url: getUrl("api/registros/amalgamacion/guardar.php"),
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

          amalgamacionTable.ajax.reload();

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
      url: getUrl("api/registros/amalgamacion/eliminar.php"),
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

          amalgamacionTable.ajax.reload();

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

    const linea = $("#registro-linea").val();
    if (!linea || linea === "add-new") {
      if (window.showErrorToast) {
        window.showErrorToast("Debe seleccionar una línea válida");
      }
      $("#registro-linea").focus();
      return false;
    }

    const amalgamador = $("#registro-amalgamador").val();
    if (!amalgamador || amalgamador === "add-new") {
      if (window.showErrorToast) {
        window.showErrorToast("Debe seleccionar un amalgamador válido");
      }
      $("#registro-amalgamador").focus();
      return false;
    }

    const cantidadCarga = $("#registro-cantidad-carga").val();
    if (!cantidadCarga || Number.parseFloat(cantidadCarga) < 0) {
      if (window.showErrorToast) {
        window.showErrorToast(
          "La cantidad de carga debe ser un valor válido mayor o igual a 0"
        );
      }
      $("#registro-cantidad-carga").focus();
      return false;
    }

    const tipoCarga = $("#registro-tipo-carga").val();
    if (!tipoCarga || tipoCarga === "add-new") {
      if (window.showErrorToast) {
        window.showErrorToast("Debe seleccionar un tipo de carga válido");
      }
      $("#registro-tipo-carga").focus();
      return false;
    }

    const mercurio = $("#registro-mercurio").val();
    if (!mercurio || Number.parseFloat(mercurio) < 0) {
      if (window.showErrorToast) {
        window.showErrorToast(
          "La carga de mercurio debe ser un valor válido mayor o igual a 0"
        );
      }
      $("#registro-mercurio").focus();
      return false;
    }

    const amalgamacion = $("#registro-amalgamacion").val();
    if (!amalgamacion || Number.parseFloat(amalgamacion) < 0) {
      if (window.showErrorToast) {
        window.showErrorToast(
          "La amalgamación debe ser un valor válido mayor o igual a 0"
        );
      }
      $("#registro-amalgamacion").focus();
      return false;
    }

    const factorConversion = $("#registro-factor-conversion").val();
    if (!factorConversion || Number.parseFloat(factorConversion) <= 0) {
      if (window.showErrorToast) {
        window.showErrorToast(
          "El factor de conversión debe ser un valor válido mayor a 0"
        );
      }
      $("#registro-factor-conversion").focus();
      return false;
    }

    return true;
  }

  // Aplicar filtros
  function aplicarFiltros() {
    const fechaInicio = $("#filtro-fecha-inicio").val();
    const fechaFin = $("#filtro-fecha-fin").val();
    const turno = $("#filtro-turno").val();
    const linea = $("#filtro-linea").val();
    const amalgamador = $("#filtro-amalgamador").val();
    const codigo = $("#filtro-codigo").val();

    filtrosActivos = {};
    if (fechaInicio) filtrosActivos.fecha_inicio = fechaInicio;
    if (fechaFin) filtrosActivos.fecha_fin = fechaFin;
    if (turno && turno !== "add-new") filtrosActivos.turno_id = turno;
    if (linea && linea !== "add-new") filtrosActivos.linea_id = linea;
    if (amalgamador && amalgamador !== "add-new")
      filtrosActivos.amalgamador_id = amalgamador;
    if (codigo) filtrosActivos.codigo = codigo;

    if (window.showInfoToast) {
      window.showInfoToast("Aplicando filtros...");
    }

    amalgamacionTable.ajax.reload();

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
    $("#filtro-linea").val("");
    $("#filtro-amalgamador").val("");
    $("#filtro-codigo").val("");

    filtrosActivos = {};

    if (window.showInfoToast) {
      window.showInfoToast("Limpiando filtros...");
    }

    amalgamacionTable.ajax.reload();

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
