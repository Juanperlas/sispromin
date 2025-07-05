/**
 * Gestión de registros de producción mina
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
let frentes = []

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
    cargarFrentes()
  }

  // Cargar turnos
  function cargarTurnos() {
    $.ajax({
      url: getUrl("api/registros/mina/obtener_turnos.php"),
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

  // Cargar frentes
  function cargarFrentes() {
    $.ajax({
      url: getUrl("api/registros/mina/obtener_frentes.php"),
      type: "GET",
      dataType: "json",
      success: (response) => {
        const selectFrente = $("#registro-frente, #filtro-frente")

        // Limpiar opciones existentes (excepto la primera)
        selectFrente.find("option:not(:first)").remove()

        if (response.success && response.data && response.data.length > 0) {
          frentes = response.data

          // Agregar nuevas opciones
          frentes.forEach((frente) => {
            selectFrente.append(`<option value="${frente.id}">${frente.nombre} (${frente.codigo})</option>`)
          })
        } else {
          // No hay datos - mostrar opción para agregar
          selectFrente.append(`<option value="" disabled>No hay frentes disponibles</option>`)
          selectFrente.append(
            `<option value="add-new" style="color: #007bff; font-weight: bold;">+ Agregar nuevo frente</option>`,
          )

          if (window.showWarningToast) {
            window.showWarningToast("No hay frentes configurados. Configure los frentes primero.")
          }
        }
      },
      error: (xhr, status, error) => {
        console.error("Error al cargar frentes:", error)

        const selectFrente = $("#registro-frente, #filtro-frente")
        selectFrente.find("option:not(:first)").remove()
        selectFrente.append(`<option value="" disabled>Error al cargar frentes</option>`)
        selectFrente.append(
          `<option value="add-new" style="color: #007bff; font-weight: bold;">+ Agregar nuevo frente</option>`,
        )

        if (window.showErrorToast) {
          window.showErrorToast("Error al cargar los frentes: " + error)
        }
      },
    })
  }

  // Manejar selección de "agregar nuevo" en turnos
  $(document).on("change", "#registro-turno, #filtro-turno", function () {
    if ($(this).val() === "add-new") {
      if (confirm("¿Desea ir al módulo de turnos para agregar un nuevo turno?")) {
        window.open(getBaseUrl() + "modulos/controles/mina/turnos/index.php", "_blank")
      }
      $(this).val("") // Resetear selección
    }
  })

  // Manejar selección de "agregar nuevo" en frentes
  $(document).on("change", "#registro-frente, #filtro-frente", function () {
    if ($(this).val() === "add-new") {
      if (confirm("¿Desea ir al módulo de frentes para agregar un nuevo frente?")) {
        window.open(getBaseUrl() + "modulos/controles/mina/frentes/index.php", "_blank")
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
        url: getUrl("api/registros/mina/listar.php"),
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
          data: "frente_nombre",
          className: "align-middle text-center",
        },
        {
          data: "material_extraido",
          className: "align-middle text-right",
          render: (data) => {
            return data ? Number.parseFloat(data).toFixed(2) : "0.00"
          },
        },
        {
          data: "desmonte",
          className: "align-middle text-right",
          render: (data) => {
            return data ? Number.parseFloat(data).toFixed(2) : "0.00"
          },
        },
        {
          data: "ley_inferido_geologo",
          className: "align-middle text-right",
          render: (data) => {
            return data ? Number.parseFloat(data).toFixed(2) : "-"
          },
        },
        {
          data: "ley_laboratorio",
          className: "align-middle text-right",
          render: (data) => {
            if (data && data !== null) {
              return `<span class="lab-indicator has-lab"></span>${Number.parseFloat(data).toFixed(2)}`
            }
            return `<span class="lab-indicator no-lab"></span>-`
          },
        },
        {
          data: "valor_calculado",
          className: "align-middle text-right valor-calculado",
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

            if (tienePermiso("registros.produccion_mina.editar")) {
              acciones += `<button type="button" class="btn-accion btn-editar-registro" data-id="${data.id}" title="Editar"><i class="bi bi-pencil"></i></button>`
            }

            if (tienePermiso("registros.produccion_mina.eliminar")) {
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
            doc.pageOrientation = "landscape"
            doc.defaultStyle = {
              fontSize: 7,
              color: "#333333",
            }

            const colores = {
              azulPastel: "#D4E6F1",
              azulOscuro: "#1A5276",
              azulPrimario: "#1571b0",
            }

            doc.content.unshift({
              text: "REPORTE DE PRODUCCIÓN MINA",
              style: "header",
              alignment: "center",
              margin: [0, 15, 0, 15],
            })

            const tableIndex = doc.content.findIndex((item) => item.table)
            if (tableIndex !== -1) {
              doc.content[tableIndex].table.widths = [8, 15, 10, 10, 12, 10, 10, 8, 8, 9]

              doc.content[tableIndex].table.body.forEach((row, i) => {
                if (i === 0) {
                  row.forEach((cell) => {
                    cell.fillColor = colores.azulPastel
                    cell.color = colores.azulOscuro
                    cell.fontSize = 8
                    cell.bold = true
                    cell.alignment = "center"
                    cell.margin = [1, 2, 1, 2]
                  })
                } else {
                  row.forEach((cell, j) => {
                    cell.fontSize = 7
                    cell.margin = [1, 1, 1, 1]
                    if (j === 1 || j === 2 || j === 3 || j === 4) {
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
              title: "Reporte de Producción Mina",
              author: "SISPROMIN",
              subject: "Listado de Registros de Producción Mina",
            }
          },
          filename: "Reporte_Produccion_Mina_" + new Date().toISOString().split("T")[0],
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
      url: getUrl("api/registros/mina/obtener.php"),
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
          if (tienePermiso("registros.produccion_mina.editar")) {
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
    const valorCalculado = data.valor_calculado ? Number.parseFloat(data.valor_calculado).toFixed(2) : "0.00"
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
                <div class="col-md-2">
                  <div class="form-group">
                    <label class="form-label form-label-sm fw-bold">ID</label>
                    <div class="form-control form-control-sm bg-light">${data.id}</div>
                  </div>
                </div>
                <div class="col-md-5">
                  <div class="form-group">
                    <label class="form-label form-label-sm fw-bold">Código</label>
                    <div class="form-control form-control-sm bg-light">${data.codigo_registro}</div>
                  </div>
                </div>
                <div class="col-md-5">
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
                    <label class="form-label form-label-sm fw-bold">Frente</label>
                    <div class="form-control form-control-sm bg-light">${data.frente_nombre}</div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-md-6">
          <div class="card-form mb-3">
            <div class="card-form-header">
              <i class="bi bi-minecart me-2"></i>Datos de Producción
            </div>
            <div class="card-form-body">
              <div class="row g-3">
                <div class="col-md-6">
                  <div class="form-group">
                    <label class="form-label form-label-sm fw-bold">Material Extraído</label>
                    <div class="form-control form-control-sm bg-light">${Number.parseFloat(data.material_extraido).toFixed(2)} TM</div>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group">
                    <label class="form-label form-label-sm fw-bold">Desmonte</label>
                    <div class="form-control form-control-sm bg-light">${Number.parseFloat(data.desmonte).toFixed(2)} TM</div>
                  </div>
                </div>
                <div class="col-md-12">
                  <div class="form-group">
                    <label class="form-label form-label-sm fw-bold">Ley Inferido Geólogo</label>
                    <div class="form-control form-control-sm bg-light">${data.ley_inferido_geologo ? Number.parseFloat(data.ley_inferido_geologo).toFixed(2) + " g/t" : "-"}</div>
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
                    <label class="form-label form-label-sm fw-bold">Valor Calculado</label>
                    <div class="form-control form-control-sm bg-success text-white fw-bold">${valorCalculado} g</div>
                    <small class="text-muted">Material extraído × ${tieneLabor ? "Ley laboratorio" : "Ley geólogo"}</small>
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
      url: getUrl("api/registros/mina/obtener.php"),
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

          const valorCalculado = data.valor_calculado ? Number.parseFloat(data.valor_calculado).toFixed(2) : "0.00"
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
                      <label class="form-label form-label-sm">ID</label>
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
                      <label class="form-label form-label-sm">Frente</label>
                      <div class="form-control form-control-sm bg-light">${data.frente_nombre}</div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div class="card-form mb-3">
              <div class="card-form-header">
                <i class="bi bi-minecart me-2"></i>Producción
              </div>
              <div class="card-form-body">
                <div class="row g-2">
                  <div class="col-md-6">
                    <div class="form-group mb-2">
                      <label class="form-label form-label-sm">Material Extraído</label>
                      <div class="form-control form-control-sm bg-light">${Number.parseFloat(data.material_extraido).toFixed(2)} TM</div>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="form-group mb-2">
                      <label class="form-label form-label-sm">Desmonte</label>
                      <div class="form-control form-control-sm bg-light">${Number.parseFloat(data.desmonte).toFixed(2)} TM</div>
                    </div>
                  </div>
                  <div class="col-md-12">
                    <div class="form-group mb-2">
                      <label class="form-label form-label-sm">Ley Geólogo</label>
                      <div class="form-control form-control-sm bg-light">${data.ley_inferido_geologo ? Number.parseFloat(data.ley_inferido_geologo).toFixed(2) + " g/t" : "-"}</div>
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
                <i class="bi bi-calculator me-2"></i>Valor Calculado
              </div>
              <div class="card-form-body">
                <div class="form-group mb-2">
                  <div class="form-control form-control-sm bg-success text-white fw-bold text-center">${valorCalculado} g</div>
                  <small class="text-muted">Material extraído × ${tieneLabor ? "Ley laboratorio" : "Ley geólogo"}</small>
                </div>
              </div>
            </div>
          `

          let botonEditar = ""
          if (tienePermiso("registros.produccion_mina.editar")) {
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
    $("#modal-registro-titulo").text("Nuevo Registro de Producción")

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
      url: getUrl("api/registros/mina/obtener.php"),
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
          $("#registro-frente").val(data.frente_id)
          $("#registro-material-extraido").val(data.material_extraido)
          $("#registro-desmonte").val(data.desmonte)
          $("#registro-ley-geologo").val(data.ley_inferido_geologo || "")

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

          $("#modal-registro-titulo").text("Editar Registro de Producción")

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
    formData.append("frente_id", $("#registro-frente").val())
    formData.append("material_extraido", $("#registro-material-extraido").val())
    formData.append("desmonte", $("#registro-desmonte").val())
    formData.append("ley_inferido_geologo", $("#registro-ley-geologo").val())

    // Campos de laboratorio si están habilitados
    if ($("#habilitar-laboratorio").is(":checked")) {
      formData.append("codigo_muestra", $("#registro-codigo-muestra").val())
      formData.append("ley_laboratorio", $("#registro-ley-laboratorio").val())
    }

    $.ajax({
      url: getUrl("api/registros/mina/guardar.php"),
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
      url: getUrl("api/registros/mina/eliminar.php"),
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

    const frente = $("#registro-frente").val()
    if (!frente || frente === "add-new") {
      if (window.showErrorToast) {
        window.showErrorToast("Debe seleccionar un frente válido")
      }
      $("#registro-frente").focus()
      return false
    }

    const materialExtraido = $("#registro-material-extraido").val()
    if (!materialExtraido || Number.parseFloat(materialExtraido) < 0) {
      if (window.showErrorToast) {
        window.showErrorToast("El material extraído debe ser un valor válido mayor o igual a 0")
      }
      $("#registro-material-extraido").focus()
      return false
    }

    const desmonte = $("#registro-desmonte").val()
    if (!desmonte || Number.parseFloat(desmonte) < 0) {
      if (window.showErrorToast) {
        window.showErrorToast("El desmonte debe ser un valor válido mayor o igual a 0")
      }
      $("#registro-desmonte").focus()
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
    const frente = $("#filtro-frente").val()
    const codigo = $("#filtro-codigo").val()

    filtrosActivos = {}
    if (fechaInicio) filtrosActivos.fecha_inicio = fechaInicio
    if (fechaFin) filtrosActivos.fecha_fin = fechaFin
    if (turno && turno !== "add-new") filtrosActivos.turno_id = turno
    if (frente && frente !== "add-new") filtrosActivos.frente_id = frente
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
    $("#filtro-frente").val("")
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
