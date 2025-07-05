/**
 * Gestión de registros de producción planta
 * Funcionalidades para listar, crear, editar y eliminar registros de producción
 */

// Declaración de variables globales
const $ = window.jQuery
const bootstrap = window.bootstrap

// Variables globales
let produccionTable
let registroSeleccionado
let modalRegistro
let modalVerRegistro
let filtrosActivos = {}
let turnos = []
let lineas = []
let concentrados = []

document.addEventListener("DOMContentLoaded", () => {
  // Función para obtener la URL base
  function getBaseUrl() {
    return window.location.pathname.split("/modulos/")[0] + "/"
  }

  // Función para construir URL completa
  function getUrl(path) {
    return getBaseUrl() + path
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
    })

    // Cargar datos iniciales
    cargarTurnos()
    cargarLineas()
    cargarConcentrados()
  }

  // Cargar turnos
  function cargarTurnos() {
    $.ajax({
      url: getUrl("api/registros/planta/obtener_turnos.php"),
      type: "GET",
      dataType: "json",
      success: (response) => {
        const selectTurno = $("#registro-turno, #filtro-turno")

        // Limpiar opciones existentes (excepto la primera)
        selectTurno.find("option:not(:first)").remove()

        if (response.success && response.data && response.data.length > 0) {
          turnos = response.data

          // Agregar nuevas opciones
          turnos.forEach((turno) => {
            selectTurno.append(`<option value="${turno.id}">${turno.nombre} (${turno.codigo})</option>`)
          })
        } else {
          // No hay datos - mostrar opción para agregar
          selectTurno.append(`<option value="" disabled>No hay turnos disponibles</option>`)
          selectTurno.append(
            `<option value="add-new" style="color: #007bff; font-weight: bold;">+ Agregar nuevo turno</option>`,
          )

          if (window.showWarningToast) {
            window.showWarningToast("No hay turnos configurados. Configure los turnos primero.")
          }
        }
      },
      error: (xhr, status, error) => {
        console.error("Error al cargar turnos:", error)

        const selectTurno = $("#registro-turno, #filtro-turno")
        selectTurno.find("option:not(:first)").remove()
        selectTurno.append(`<option value="" disabled>Error al cargar turnos</option>`)
        selectTurno.append(
          `<option value="add-new" style="color: #007bff; font-weight: bold;">+ Agregar nuevo turno</option>`,
        )

        if (window.showErrorToast) {
          window.showErrorToast("Error al cargar los turnos: " + error)
        }
      },
    })
  }

  // Cargar líneas
  function cargarLineas() {
    $.ajax({
      url: getUrl("api/registros/planta/obtener_lineas.php"),
      type: "GET",
      dataType: "json",
      success: (response) => {
        const selectLinea = $("#registro-linea, #filtro-linea")

        // Limpiar opciones existentes (excepto la primera)
        selectLinea.find("option:not(:first)").remove()

        if (response.success && response.data && response.data.length > 0) {
          lineas = response.data

          // Agregar nuevas opciones
          lineas.forEach((linea) => {
            selectLinea.append(`<option value="${linea.id}">${linea.nombre} (${linea.codigo})</option>`)
          })
        } else {
          // No hay datos - mostrar opción para agregar
          selectLinea.append(`<option value="" disabled>No hay líneas disponibles</option>`)
          selectLinea.append(
            `<option value="add-new" style="color: #007bff; font-weight: bold;">+ Agregar nueva línea</option>`,
          )

          if (window.showWarningToast) {
            window.showWarningToast("No hay líneas configuradas. Configure las líneas primero.")
          }
        }
      },
      error: (xhr, status, error) => {
        console.error("Error al cargar líneas:", error)

        const selectLinea = $("#registro-linea, #filtro-linea")
        selectLinea.find("option:not(:first)").remove()
        selectLinea.append(`<option value="" disabled>Error al cargar líneas</option>`)
        selectLinea.append(
          `<option value="add-new" style="color: #007bff; font-weight: bold;">+ Agregar nueva línea</option>`,
        )

        if (window.showErrorToast) {
          window.showErrorToast("Error al cargar las líneas: " + error)
        }
      },
    })
  }

  // Cargar concentrados
  function cargarConcentrados() {
    $.ajax({
      url: getUrl("api/registros/planta/obtener_concentrados.php"),
      type: "GET",
      dataType: "json",
      success: (response) => {
        const selectConcentrado = $("#registro-concentrado, #filtro-concentrado")

        // Limpiar opciones existentes (excepto la primera)
        selectConcentrado.find("option:not(:first)").remove()

        if (response.success && response.data && response.data.length > 0) {
          concentrados = response.data

          // Agregar nuevas opciones
          concentrados.forEach((concentrado) => {
            selectConcentrado.append(
              `<option value="${concentrado.id}">${concentrado.nombre} (${concentrado.codigo})</option>`,
            )
          })
        } else {
          // No hay datos - mostrar opción para agregar
          selectConcentrado.append(`<option value="" disabled>No hay concentrados disponibles</option>`)
          selectConcentrado.append(
            `<option value="add-new" style="color: #007bff; font-weight: bold;">+ Agregar nuevo concentrado</option>`,
          )

          if (window.showWarningToast) {
            window.showWarningToast("No hay concentrados configurados. Configure los concentrados primero.")
          }
        }
      },
      error: (xhr, status, error) => {
        console.error("Error al cargar concentrados:", error)

        const selectConcentrado = $("#registro-concentrado, #filtro-concentrado")
        selectConcentrado.find("option:not(:first)").remove()
        selectConcentrado.append(`<option value="" disabled>Error al cargar concentrados</option>`)
        selectConcentrado.append(
          `<option value="add-new" style="color: #007bff; font-weight: bold;">+ Agregar nuevo concentrado</option>`,
        )

        if (window.showErrorToast) {
          window.showErrorToast("Error al cargar los concentrados: " + error)
        }
      },
    })
  }

  // Manejar selección de "agregar nuevo" en turnos
  $(document).on("change", "#registro-turno, #filtro-turno", function () {
    if ($(this).val() === "add-new") {
      if (confirm("¿Desea ir al módulo de turnos para agregar un nuevo turno?")) {
        window.open(getBaseUrl() + "modulos/controles/planta/turnos/index.php", "_blank")
      }
      $(this).val("") // Resetear selección
    }
  })

  // Manejar selección de "agregar nuevo" en líneas
  $(document).on("change", "#registro-linea, #filtro-linea", function () {
    if ($(this).val() === "add-new") {
      if (confirm("¿Desea ir al módulo de líneas para agregar una nueva línea?")) {
        window.open(getBaseUrl() + "modulos/controles/planta/lineas/index.php", "_blank")
      }
      $(this).val("") // Resetear selección
    }
  })

  // Manejar selección de "agregar nuevo" en concentrados
  $(document).on("change", "#registro-concentrado, #filtro-concentrado", function () {
    if ($(this).val() === "add-new") {
      if (confirm("¿Desea ir al módulo de concentrados para agregar un nuevo concentrado?")) {
        window.open(getBaseUrl() + "modulos/controles/planta/concentrados/index.php", "_blank")
      }
      $(this).val("") // Resetear selección
    }
  })

  // Inicializar DataTable
  function initDataTable() {
    produccionTable = $("#produccion-table").DataTable({
      processing: true,
      serverSide: true,
      responsive: true,
      ajax: {
        url: getUrl("api/registros/planta/listar.php"),
        type: "POST",
        data: (d) => {
          if (d.length == -1) {
            d.length = 10000
          }
          return {
            ...d,
            ...filtrosActivos,
          }
        },
        error: (xhr, error, thrown) => {
          console.error("Error en la solicitud AJAX de DataTable:", error, thrown)
          if (window.showErrorToast) {
            window.showErrorToast("Error al cargar los datos de la tabla: " + thrown)
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
          data: "material_procesado",
          className: "align-middle text-center",
          render: (data) => {
            return data ? Number.parseFloat(data).toFixed(2) + "tm" : "0.00"
          },
        },
        {
          data: "concentrado_nombre",
          className: "align-middle text-center",
        },
        {
          data: "produccion_cantidad",
          className: "align-middle text-center",
          render: (data) => {
            return data ? Number.parseFloat(data).toFixed(2) : "0.00"
          },
        },
        {
          data: "peso_aproximado_kg",
          className: "align-middle text-center",
          render: (data) => {
            return data ? Number.parseFloat(data).toFixed(2) : "-"
          },
        },
        {
          data: "carga_aproximada",
          className: "align-middle text-center",
          render: (data) => {
            return data ? Number.parseFloat(data).toFixed(2) : "0.00"
          },
        },
        {
          data: "ley_inferido_metalurgista",
          className: "align-middle text-center",
          render: (data) => {
            return data ? Number.parseFloat(data).toFixed(2) : "-"
          },
        },
        {
          data: "ley_laboratorio",
          className: "align-middle text-center",
          render: (data) => {
            if (data && data !== null) {
              return `<span class="lab-indicator has-lab"></span>${Number.parseFloat(data).toFixed(2)}`
            }
            return `<span class="lab-indicator no-lab"></span>-`
          },
        },
        {
          data: "produccion_estimada",
          className: "align-middle text-center valor-calculado",
          render: (data) => {
            return data ? Number.parseFloat(data).toFixed(2) : "0.00"
          },
        },
        {
          data: null,
          orderable: false,
          className: "text-center align-middle",
          render: (data) => {
            let acciones = '<div class="btn-group btn-group-sm">'
            acciones += `<button type="button" class="btn-accion btn-ver-registro" data-id="${data.id}" title="Ver detalles"><i class="bi bi-eye"></i></button>`

            if (tienePermiso("registros.produccion_planta.editar")) {
              acciones += `<button type="button" class="btn-accion btn-editar-registro" data-id="${data.id}" title="Editar"><i class="bi bi-pencil"></i></button>`
            }

            if (tienePermiso("registros.produccion_planta.eliminar")) {
              acciones += `<button type="button" class="btn-accion btn-eliminar-registro" data-id="${data.id}" title="Eliminar"><i class="bi bi-trash"></i></button>`
            }

            acciones += "</div>"
            return acciones
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
            columns: [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12],
          },
        },
        {
          extend: "excel",
          text: '<i class="bi bi-file-earmark-excel"></i> Excel',
          className: "btn btn-sm",
          exportOptions: {
            columns: [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12],
          },
        },
        {
          extend: "pdf",
          text: '<i class="bi bi-file-earmark-pdf"></i> PDF',
          className: "btn btn-sm",
          exportOptions: {
            columns: [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12],
          },
          customize: (doc) => {
            doc.pageOrientation = "landscape"
            doc.defaultStyle = {
              fontSize: 6,
              color: "#333333",
            }

            const colores = {
              azulPastel: "#D4E6F1",
              azulOscuro: "#1A5276",
              azulPrimario: "#1571b0",
            }

            doc.content.unshift({
              text: "REPORTE DE PRODUCCIÓN PLANTA",
              style: "header",
              alignment: "center",
              margin: [0, 15, 0, 15],
            })

            const tableIndex = doc.content.findIndex((item) => item.table)
            if (tableIndex !== -1) {
              doc.content[tableIndex].table.widths = [6, 12, 8, 8, 8, 8, 10, 8, 8, 8, 8, 8, 8]

              doc.content[tableIndex].table.body.forEach((row, i) => {
                if (i === 0) {
                  row.forEach((cell) => {
                    cell.fillColor = colores.azulPastel
                    cell.color = colores.azulOscuro
                    cell.fontSize = 7
                    cell.bold = true
                    cell.alignment = "center"
                    cell.margin = [1, 2, 1, 2]
                  })
                } else {
                  row.forEach((cell, j) => {
                    cell.fontSize = 6
                    cell.margin = [1, 1, 1, 1]
                    if (j === 1 || j === 2 || j === 3 || j === 4 || j === 6) {
                      cell.alignment = "center"
                    } else if (j >= 5) {
                      cell.alignment = "right"
                    } else {
                      cell.alignment = "center"
                    }
                  })

                  if (i % 2 === 0) {
                    row.forEach((cell) => {
                      cell.fillColor = "#f9f9f9"
                    })
                  }
                }
              })
            }

            doc.footer = (currentPage, pageCount) => ({
              text: `Página ${currentPage} de ${pageCount}`,
              alignment: "center",
              fontSize: 7,
              margin: [0, 0, 0, 0],
            })

            doc.styles = {
              header: {
                fontSize: 12,
                bold: true,
                color: colores.azulPrimario,
              },
            }

            doc.info = {
              title: "Reporte de Producción Planta",
              author: "SISPROMIN",
              subject: "Listado de Registros de Producción Planta",
            }
          },
          filename: "Reporte_Produccion_Planta_" + new Date().toISOString().split("T")[0],
          orientation: "landscape",
        },
        {
          extend: "print",
          text: '<i class="bi bi-printer"></i> Imprimir',
          className: "btn btn-sm",
          exportOptions: {
            columns: [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12],
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
        $("#produccion-table tbody").on("click", "tr", function () {
          const data = produccionTable.row(this).data()
          if (data) {
            $("#produccion-table tbody tr").removeClass("selected")
            $(this).addClass("selected")

            $("#registro-detalle").removeClass("loaded").addClass("loading")

            setTimeout(() => {
              cargarDetallesRegistro(data.id)
              $("#registro-detalle").removeClass("loading").addClass("loaded")
            }, 300)
          }
        })
      },
      drawCallback: () => {
        if (window.hideLoading) {
          window.hideLoading()
        }
      },
      preDrawCallback: () => {
        if (window.showLoading) {
          window.showLoading()
        }
      },
    })
  }

  // Función para mostrar modal con detalles del registro
  function mostrarModalDetalles(id) {
    modalVerRegistro = new bootstrap.Modal(document.getElementById("modal-ver-registro"))

    // Resetear contenido del modal
    $("#modal-ver-registro-body").html(`
      <div class="text-center p-4">
        <div class="spinner-border text-primary" role="status"></div>
        <p class="mt-2">Cargando detalles...</p>
      </div>
    `)

    $("#btn-editar-desde-modal").hide()
    modalVerRegistro.show()

    $.ajax({
      url: getUrl("api/registros/planta/obtener.php"),
      type: "GET",
      data: { id: id },
      dataType: "json",
      success: (response) => {
        if (response.success && response.data) {
          const data = response.data
          registroSeleccionado = data

          $("#modal-ver-registro-titulo").html(`
            <i class="bi bi-eye me-2"></i>Detalles del Registro: ${data.codigo_registro}
          `)

          const contenidoModal = generarContenidoModalDetalle(data)
          $("#modal-ver-registro-body").html(contenidoModal)

          // Mostrar botón de editar si tiene permisos
          if (tienePermiso("registros.produccion_planta.editar")) {
            $("#btn-editar-desde-modal")
              .show()
              .off("click")
              .on("click", () => {
                modalVerRegistro.hide()
                setTimeout(() => {
                  abrirModalEditar(data.id)
                }, 300)
              })
          }
        } else {
          $("#modal-ver-registro-body").html(`
            <div class="alert alert-danger">
              <i class="bi bi-exclamation-triangle me-2"></i>
              Error al cargar los detalles del registro
            </div>
          `)
        }
      },
      error: (xhr, status, error) => {
        $("#modal-ver-registro-body").html(`
          <div class="alert alert-danger">
            <i class="bi bi-exclamation-triangle me-2"></i>
            Error de conexión al servidor
          </div>
        `)
        console.error("Error al obtener detalles del registro:", error)
      },
    })
  }

  // Generar contenido del modal de detalles
  function generarContenidoModalDetalle(data) {
    const produccionEstimada = data.produccion_estimada
      ? Number.parseFloat(data.produccion_estimada).toFixed(2)
      : "0.00"
    const cargaAproximada = data.carga_aproximada ? Number.parseFloat(data.carga_aproximada).toFixed(2) : "0.00"
    const tieneLabor = data.ley_laboratorio && data.ley_laboratorio !== null

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
                    <div class="form-control form-control-sm bg-light">${data.id}</div>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group">
                    <label class="form-label form-label-sm fw-bold">Código</label>
                    <div class="form-control form-control-sm bg-light">${data.codigo_registro}</div>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group">
                    <label class="form-label form-label-sm fw-bold">Fecha</label>
                    <div class="form-control form-control-sm bg-light">${data.fecha_formateada}</div>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group">
                    <label class="form-label form-label-sm fw-bold">Turno</label>
                    <div class="form-control form-control-sm bg-light">${data.turno_nombre}</div>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group">
                    <label class="form-label form-label-sm fw-bold">Línea</label>
                    <div class="form-control form-control-sm bg-light">${data.linea_nombre}</div>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group">
                    <label class="form-label form-label-sm fw-bold">Concentrado</label>
                    <div class="form-control form-control-sm bg-light">${data.concentrado_nombre}</div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-md-6">
          <div class="card-form mb-3">
            <div class="card-form-header">
              <i class="bi bi-gear me-2"></i>Datos de Producción
            </div>
            <div class="card-form-body">
              <div class="row g-3">
                <div class="col-md-6">
                  <div class="form-group">
                    <label class="form-label form-label-sm fw-bold">Material Procesado</label>
                    <div class="form-control form-control-sm bg-light">${Number.parseFloat(data.material_procesado).toFixed(2)} TM</div>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group">
                    <label class="form-label form-label-sm fw-bold">Producción Cantidad</label>
                    <div class="form-control form-control-sm bg-light">${Number.parseFloat(data.produccion_cantidad).toFixed(2)}</div>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group">
                    <label class="form-label form-label-sm fw-bold">Peso Aproximado</label>
                    <div class="form-control form-control-sm bg-light">${data.peso_aproximado_kg ? Number.parseFloat(data.peso_aproximado_kg).toFixed(2) + " Kg" : "-"}</div>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group">
                    <label class="form-label form-label-sm fw-bold">Carga Aproximada</label>
                    <div class="form-control form-control-sm bg-warning text-dark fw-bold">${cargaAproximada}</div>
                  </div>
                </div>
                <div class="col-md-12">
                  <div class="form-group">
                    <label class="form-label form-label-sm fw-bold">Ley Inferido Metalurgista</label>
                    <div class="form-control form-control-sm bg-light">${data.ley_inferido_metalurgista ? Number.parseFloat(data.ley_inferido_metalurgista).toFixed(2) + " g/t" : "-"}</div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-md-6">
          <div class="card-form mb-3">
            <div class="card-form-header">
              <i class="bi bi-flask me-2"></i>Datos de Laboratorio
            </div>
            <div class="card-form-body">
              <div class="row g-3">
                <div class="col-md-12">
                  <div class="form-group">
                    <label class="form-label form-label-sm fw-bold">Código de Muestra</label>
                    <div class="form-control form-control-sm bg-light">${data.codigo_muestra || "-"}</div>
                  </div>
                </div>
                <div class="col-md-12">
                  <div class="form-group">
                    <label class="form-label form-label-sm fw-bold">Ley Laboratorio</label>
                    <div class="form-control form-control-sm bg-light">${data.ley_laboratorio ? Number.parseFloat(data.ley_laboratorio).toFixed(2) + " g/t" : "-"}</div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-md-6">
          <div class="card-form mb-3">
            <div class="card-form-header">
              <i class="bi bi-calculator me-2"></i>Cálculos
            </div>
            <div class="card-form-body">
              <div class="row g-3">
                <div class="col-md-12">
                  <div class="form-group">
                    <label class="form-label form-label-sm fw-bold">Producción Estimada</label>
                    <div class="form-control form-control-sm bg-success text-white fw-bold">${produccionEstimada} g</div>
                    <small class="text-muted">Material procesado × ${tieneLabor ? "Ley laboratorio" : "Ley metalurgista"}</small>
                  </div>
                </div>
                <div class="col-md-12">
                  <div class="form-group">
                    <label class="form-label form-label-sm fw-bold">Fecha de Creación</label>
                    <div class="form-control form-control-sm bg-light">${formatearFecha(data.creado_en)}</div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    `
  }

  // Función para cargar detalles del registro en el panel lateral
  function cargarDetallesRegistro(id) {
    $("#registro-detalle .detail-content").html(
      '<div class="text-center p-4"><div class="spinner-border text-primary" role="status"></div><p class="mt-2">Cargando detalles...</p></div>',
    )
    $("#registro-detalle").addClass("active")

    $.ajax({
      url: getUrl("api/registros/planta/obtener.php"),
      type: "GET",
      data: { id: id },
      dataType: "json",
      success: (response) => {
        if (response.success && response.data) {
          const data = response.data
          registroSeleccionado = data

          $("#registro-detalle .detail-header").html(`
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <h2 class="detail-title">Registro: ${data.codigo_registro}</h2>
                <p class="detail-subtitle">Fecha: ${data.fecha_formateada}</p>
              </div>
            </div>
          `)

          const produccionEstimada = data.produccion_estimada
            ? Number.parseFloat(data.produccion_estimada).toFixed(2)
            : "0.00"
          const cargaAproximada = data.carga_aproximada ? Number.parseFloat(data.carga_aproximada).toFixed(2) : "0.00"
          const tieneLabor = data.ley_laboratorio && data.ley_laboratorio !== null

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
                      <div class="form-control form-control-sm bg-light">${data.id}</div>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="form-group mb-2">
                      <label class="form-label form-label-sm">Código</label>
                      <div class="form-control form-control-sm bg-light">${data.codigo_registro}</div>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="form-group mb-2">
                      <label class="form-label form-label-sm">Turno</label>
                      <div class="form-control form-control-sm bg-light">${data.turno_nombre}</div>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="form-group mb-2">
                      <label class="form-label form-label-sm">Línea</label>
                      <div class="form-control form-control-sm bg-light">${data.linea_nombre}</div>
                    </div>
                  </div>
                  <div class="col-md-12">
                    <div class="form-group mb-2">
                      <label class="form-label form-label-sm">Concentrado</label>
                      <div class="form-control form-control-sm bg-light">${data.concentrado_nombre}</div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div class="card-form mb-3">
              <div class="card-form-header">
                <i class="bi bi-gear me-2"></i>Producción
              </div>
              <div class="card-form-body">
                <div class="row g-2">
                  <div class="col-md-6">
                    <div class="form-group mb-2">
                      <label class="form-label form-label-sm">Material Procesado</label>
                      <div class="form-control form-control-sm bg-light">${Number.parseFloat(data.material_procesado).toFixed(2)} TM</div>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="form-group mb-2">
                      <label class="form-label form-label-sm">Prod. Cantidad</label>
                      <div class="form-control form-control-sm bg-light">${Number.parseFloat(data.produccion_cantidad).toFixed(2)}</div>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="form-group mb-2">
                      <label class="form-label form-label-sm">Peso Aprox.</label>
                      <div class="form-control form-control-sm bg-light">${data.peso_aproximado_kg ? Number.parseFloat(data.peso_aproximado_kg).toFixed(2) + " Kg" : "-"}</div>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="form-group mb-2">
                      <label class="form-label form-label-sm">Carga Aprox.</label>
                      <div class="form-control form-control-sm bg-warning text-dark fw-bold">${cargaAproximada}</div>
                    </div>
                  </div>
                  <div class="col-md-12">
                    <div class="form-group mb-2">
                      <label class="form-label form-label-sm">Ley Metalurgista</label>
                      <div class="form-control form-control-sm bg-light">${data.ley_inferido_metalurgista ? Number.parseFloat(data.ley_inferido_metalurgista).toFixed(2) + " g/t" : "-"}</div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div class="card-form mb-3">
              <div class="card-form-header">
                <i class="bi bi-flask me-2"></i>Laboratorio
              </div>
              <div class="card-form-body">
                <div class="row g-2">
                  <div class="col-md-12">
                    <div class="form-group mb-2">
                      <label class="form-label form-label-sm">Código Muestra</label>
                      <div class="form-control form-control-sm bg-light">${data.codigo_muestra || "-"}</div>
                    </div>
                  </div>
                  <div class="col-md-12">
                    <div class="form-group mb-2">
                      <label class="form-label form-label-sm">Ley Laboratorio</label>
                      <div class="form-control form-control-sm bg-light">${data.ley_laboratorio ? Number.parseFloat(data.ley_laboratorio).toFixed(2) + " g/t" : "-"}</div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div class="card-form mb-3">
              <div class="card-form-header">
                <i class="bi bi-calculator me-2"></i>Producción Estimada
              </div>
              <div class="card-form-body">
                <div class="form-group mb-2">
                  <div class="form-control form-control-sm bg-success text-white fw-bold text-center">${produccionEstimada} g</div>
                  <small class="text-muted">Material procesado × ${tieneLabor ? "Ley laboratorio" : "Ley metalurgista"}</small>
                </div>
              </div>
            </div>
          `

          let botonEditar = ""
          if (tienePermiso("registros.produccion_planta.editar")) {
            botonEditar = `
              <div class="d-grid gap-2 mt-3">
                <button type="button" id="btn-editar-panel" class="btn btn-warning" data-id="${data.id}">
                  <i class="bi bi-pencil me-2"></i>Editar Registro
                </button>
              </div>
            `
          }

          $("#registro-detalle .detail-content").html(`
            ${infoBasica}
            ${botonEditar}
          `)

          $("#btn-editar-panel").on("click", function () {
            const id = $(this).data("id")
            abrirModalEditar(id)
          })
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
          `)
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
        `)
        console.error("Error al obtener detalles del registro:", error)
      },
    })
  }

  // Función para abrir modal de crear registro
  function abrirModalCrear() {
    $("#form-registro")[0].reset()
    $("#registro-id").val("")
    $("#modal-registro-titulo").text("Nuevo Registro de Producción Planta")

    // Establecer fecha actual
    const hoy = new Date()
    const fechaFormateada = hoy.toLocaleDateString("es-ES")
    $("#registro-fecha").val(fechaFormateada)

    // Deshabilitar campos de laboratorio
    $("#habilitar-laboratorio").prop("checked", false)
    $("#registro-codigo-muestra, #registro-ley-laboratorio").prop("disabled", true).val("")

    modalRegistro = new bootstrap.Modal(document.getElementById("modal-registro"))
    modalRegistro.show()
  }

  // Función para abrir modal de editar registro
  function abrirModalEditar(id) {
    showLoadingOverlay()

    $.ajax({
      url: getUrl("api/registros/planta/obtener.php"),
      type: "GET",
      data: { id: id },
      dataType: "json",
      success: (response) => {
        hideLoadingOverlay()

        if (response.success && response.data) {
          const data = response.data

          $("#registro-id").val(data.id)
          $("#registro-fecha").val(data.fecha_formateada)
          $("#registro-turno").val(data.turno_id)
          $("#registro-linea").val(data.linea_id)
          $("#registro-concentrado").val(data.concentrado_id)
          $("#registro-material-procesado").val(data.material_procesado)
          $("#registro-produccion-cantidad").val(data.produccion_cantidad)
          $("#registro-peso-aproximado").val(data.peso_aproximado_kg || "")
          $("#registro-ley-metalurgista").val(data.ley_inferido_metalurgista || "")

          // Configurar campos de laboratorio
          if (data.codigo_muestra || data.ley_laboratorio) {
            $("#habilitar-laboratorio").prop("checked", true)
            $("#registro-codigo-muestra, #registro-ley-laboratorio").prop("disabled", false)
            $("#registro-codigo-muestra").val(data.codigo_muestra || "")
            $("#registro-ley-laboratorio").val(data.ley_laboratorio || "")
          } else {
            $("#habilitar-laboratorio").prop("checked", false)
            $("#registro-codigo-muestra, #registro-ley-laboratorio").prop("disabled", true).val("")
          }

          $("#modal-registro-titulo").text("Editar Registro de Producción Planta")

          modalRegistro = new bootstrap.Modal(document.getElementById("modal-registro"))
          modalRegistro.show()
        } else {
          if (window.showErrorToast) {
            window.showErrorToast(response.message || "Error al obtener los datos del registro")
          }
        }
      },
      error: (xhr, status, error) => {
        hideLoadingOverlay()
        if (window.showErrorToast) {
          window.showErrorToast("Error de conexión al servidor")
        }
        console.error("Error al obtener registro:", error)
      },
    })
  }

  // Función para guardar registro
  function guardarRegistro() {
    if (!validarFormulario()) {
      return
    }

    showLoadingOverlay()

    const formData = new FormData()
    const id = $("#registro-id").val()

    if (id) {
      formData.append("id", id)
    }

    // Convertir fecha de dd/mm/yyyy a yyyy-mm-dd
    const fechaInput = $("#registro-fecha").val()
    const fechaParts = fechaInput.split("/")
    const fechaFormatted = `${fechaParts[2]}-${fechaParts[1]}-${fechaParts[0]}`

    formData.append("fecha", fechaFormatted)
    formData.append("turno_id", $("#registro-turno").val())
    formData.append("linea_id", $("#registro-linea").val())
    formData.append("concentrado_id", $("#registro-concentrado").val())
    formData.append("material_procesado", $("#registro-material-procesado").val())
    formData.append("produccion_cantidad", $("#registro-produccion-cantidad").val())
    formData.append("peso_aproximado_kg", $("#registro-peso-aproximado").val())
    formData.append("ley_inferido_metalurgista", $("#registro-ley-metalurgista").val())

    // Campos de laboratorio si están habilitados
    if ($("#habilitar-laboratorio").is(":checked")) {
      formData.append("codigo_muestra", $("#registro-codigo-muestra").val())
      formData.append("ley_laboratorio", $("#registro-ley-laboratorio").val())
    }

    $.ajax({
      url: getUrl("api/registros/planta/guardar.php"),
      type: "POST",
      data: formData,
      processData: false,
      contentType: false,
      dataType: "json",
      success: (response) => {
        hideLoadingOverlay()

        if (response.success) {
          if (modalRegistro) {
            modalRegistro.hide()
          }

          if (window.showSuccessToast) {
            window.showSuccessToast(response.message || "Registro guardado correctamente")
          }

          produccionTable.ajax.reload()

          if (registroSeleccionado && registroSeleccionado.id == id) {
            $("#registro-detalle").removeClass("loaded").addClass("loading")
            setTimeout(() => {
              cargarDetallesRegistro(id)
              $("#registro-detalle").removeClass("loading").addClass("loaded")
            }, 300)
          }
        } else {
          if (window.showErrorToast) {
            window.showErrorToast(response.message || "Error al guardar el registro")
          }
        }
      },
      error: (xhr, status, error) => {
        hideLoadingOverlay()
        if (window.showErrorToast) {
          window.showErrorToast("Error de conexión al servidor")
        }
        console.error("Error al guardar registro:", error)
      },
    })
  }

  // Función para eliminar registro
  function eliminarRegistro(id) {
    if (!confirm("¿Está seguro de que desea eliminar este registro?")) {
      return
    }

    showLoadingOverlay()

    $.ajax({
      url: getUrl("api/registros/planta/eliminar.php"),
      type: "POST",
      data: { id: id },
      dataType: "json",
      success: (response) => {
        hideLoadingOverlay()

        if (response.success) {
          if (window.showSuccessToast) {
            window.showSuccessToast(response.message || "Registro eliminado correctamente")
          }

          produccionTable.ajax.reload()

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
            `)
            $("#registro-detalle").removeClass("active")
            registroSeleccionado = null
          }
        } else {
          if (window.showErrorToast) {
            window.showErrorToast(response.message || "Error al eliminar el registro")
          }
        }
      },
      error: (xhr, status, error) => {
        hideLoadingOverlay()
        if (window.showErrorToast) {
          window.showErrorToast("Error de conexión al servidor")
        }
        console.error("Error al eliminar registro:", error)
      },
    })
  }

  // Función para validar el formulario
  function validarFormulario() {
    const fecha = $("#registro-fecha").val()
    if (!fecha) {
      if (window.showErrorToast) {
        window.showErrorToast("La fecha es obligatoria")
      }
      $("#registro-fecha").focus()
      return false
    }

    const turno = $("#registro-turno").val()
    if (!turno || turno === "add-new") {
      if (window.showErrorToast) {
        window.showErrorToast("Debe seleccionar un turno válido")
      }
      $("#registro-turno").focus()
      return false
    }

    const linea = $("#registro-linea").val()
    if (!linea || linea === "add-new") {
      if (window.showErrorToast) {
        window.showErrorToast("Debe seleccionar una línea válida")
      }
      $("#registro-linea").focus()
      return false
    }

    const concentrado = $("#registro-concentrado").val()
    if (!concentrado || concentrado === "add-new") {
      if (window.showErrorToast) {
        window.showErrorToast("Debe seleccionar un concentrado válido")
      }
      $("#registro-concentrado").focus()
      return false
    }

    const materialProcesado = $("#registro-material-procesado").val()
    if (!materialProcesado || Number.parseFloat(materialProcesado) < 0) {
      if (window.showErrorToast) {
        window.showErrorToast("El material procesado debe ser un valor válido mayor o igual a 0")
      }
      $("#registro-material-procesado").focus()
      return false
    }

    const produccionCantidad = $("#registro-produccion-cantidad").val()
    if (!produccionCantidad || Number.parseFloat(produccionCantidad) < 0) {
      if (window.showErrorToast) {
        window.showErrorToast("La producción cantidad debe ser un valor válido mayor o igual a 0")
      }
      $("#registro-produccion-cantidad").focus()
      return false
    }

    // Validar campos de laboratorio si están habilitados
    if ($("#habilitar-laboratorio").is(":checked")) {
      const codigoMuestra = $("#registro-codigo-muestra").val()
      const leyLaboratorio = $("#registro-ley-laboratorio").val()

      if (!codigoMuestra) {
        if (window.showErrorToast) {
          window.showErrorToast("El código de muestra es obligatorio cuando se habilita laboratorio")
        }
        $("#registro-codigo-muestra").focus()
        return false
      }

      if (!leyLaboratorio || Number.parseFloat(leyLaboratorio) < 0) {
        if (window.showErrorToast) {
          window.showErrorToast("La ley de laboratorio debe ser un valor válido mayor o igual a 0")
        }
        $("#registro-ley-laboratorio").focus()
        return false
      }
    }

    return true
  }

  // Aplicar filtros
  function aplicarFiltros() {
    const fechaInicio = $("#filtro-fecha-inicio").val()
    const fechaFin = $("#filtro-fecha-fin").val()
    const turno = $("#filtro-turno").val()
    const linea = $("#filtro-linea").val()
    const concentrado = $("#filtro-concentrado").val()
    const codigo = $("#filtro-codigo").val()

    filtrosActivos = {}
    if (fechaInicio) filtrosActivos.fecha_inicio = fechaInicio
    if (fechaFin) filtrosActivos.fecha_fin = fechaFin
    if (turno && turno !== "add-new") filtrosActivos.turno_id = turno
    if (linea && linea !== "add-new") filtrosActivos.linea_id = linea
    if (concentrado && concentrado !== "add-new") filtrosActivos.concentrado_id = concentrado
    if (codigo) filtrosActivos.codigo = codigo

    if (window.showInfoToast) {
      window.showInfoToast("Aplicando filtros...")
    }

    produccionTable.ajax.reload()

    $("#registro-detalle .detail-content").html(`
      <div class="detail-empty">
        <div class="detail-empty-icon">
          <i class="bi bi-info-circle"></i>
        </div>
        <div class="detail-empty-text">
          Seleccione un registro para ver sus detalles
        </div>
      </div>
    `)
    $("#registro-detalle").removeClass("active")
    registroSeleccionado = null
  }

  // Limpiar filtros
  function limpiarFiltros() {
    $("#filtro-fecha-inicio").val("")
    $("#filtro-fecha-fin").val("")
    $("#filtro-turno").val("")
    $("#filtro-linea").val("")
    $("#filtro-concentrado").val("")
    $("#filtro-codigo").val("")

    filtrosActivos = {}

    if (window.showInfoToast) {
      window.showInfoToast("Limpiando filtros...")
    }

    produccionTable.ajax.reload()

    $("#registro-detalle .detail-content").html(`
      <div class="detail-empty">
        <div class="detail-empty-icon">
          <i class="bi bi-info-circle"></i>
        </div>
        <div class="detail-empty-text">
          Seleccione un registro para ver sus detalles
        </div>
      </div>
    `)
    $("#registro-detalle").removeClass("active")
    registroSeleccionado = null
  }

  // Función para formatear fechas
  function formatearFecha(fecha) {
    if (!fecha) return "-"
    const date = new Date(fecha)
    return date.toLocaleDateString("es-ES", {
      day: "2-digit",
      month: "2-digit",
      year: "numeric",
      hour: "2-digit",
      minute: "2-digit",
    })
  }

  // Mostrar indicador de carga
  function showLoadingOverlay() {
    if (typeof window.showLoading === "function") {
      window.showLoading()
    }
  }

  // Ocultar indicador de carga
  function hideLoadingOverlay() {
    if (typeof window.hideLoading === "function") {
      window.hideLoading()
    }
  }

  // Verificar si el usuario tiene un permiso específico
  function tienePermiso(permiso) {
    if (typeof window.tienePermiso === "function") {
      return window.tienePermiso(permiso)
    }
    return true
  }

  // Inicializar componentes
  inicializarComponentes()
  initDataTable()

  // Event Listeners
  $("#btn-nuevo-registro").on("click", () => {
    abrirModalCrear()
  })

  $("#btn-guardar-registro").on("click", () => {
    guardarRegistro()
  })

  // Event listener para el checkbox de laboratorio
  $("#habilitar-laboratorio").on("change", function () {
    const habilitado = $(this).is(":checked")
    $("#registro-codigo-muestra, #registro-ley-laboratorio").prop("disabled", !habilitado)

    if (!habilitado) {
      $("#registro-codigo-muestra, #registro-ley-laboratorio").val("")
    }
  })

  // Event listener para el botón de ver detalles (modal)
  $(document).on("click", ".btn-ver-registro", function (e) {
    e.stopPropagation()
    const id = $(this).data("id")
    mostrarModalDetalles(id)
  })

  $(document).on("click", ".btn-editar-registro", function (e) {
    e.stopPropagation()
    const id = $(this).data("id")
    abrirModalEditar(id)
  })

  $(document).on("click", ".btn-eliminar-registro", function (e) {
    e.stopPropagation()
    const id = $(this).data("id")
    eliminarRegistro(id)
  })

  $("#btn-aplicar-filtros").on("click", () => {
    aplicarFiltros()
  })

  $("#btn-limpiar-filtros").on("click", () => {
    limpiarFiltros()
  })

  // Inicializar tooltips
  const tooltips = document.querySelectorAll("[title]")
  tooltips.forEach((tooltip) => {
    try {
      new bootstrap.Tooltip(tooltip)
    } catch (e) {
      console.warn("Error al inicializar tooltip:", e)
    }
  })
})

// Funciones de toast si no existen
if (!window.showErrorToast) {
  window.showErrorToast = (msg) => console.error(msg)
}
if (!window.showSuccessToast) {
  window.showSuccessToast = (msg) => console.log(msg)
}
if (!window.showInfoToast) {
  window.showInfoToast = (msg) => console.log(msg)
}
if (!window.showWarningToast) {
  window.showWarningToast = (msg) => console.warn(msg)
}
