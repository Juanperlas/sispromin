/**
 * Colores Globales - SISPROMIN
 * Constantes JavaScript para mantener consistencia en todo el sistema
 */

// Configuración de colores
const colors = {
  primary: "#1571b0",
  primaryDark: "#125f95",
  primaryLight: "rgba(21, 113, 176, 0.1)",
  success: "#20c997",
  warning: "#ff8c00",
  danger: "#e63946",
  info: "#17a2b8",
  secondary: "#6c757d",
  light: "#f8f9fa",
  dark: "#212529",

  // Colores para exportación
  excel: "#217346",
  pdf: "#e63946",
  copy: "#6c757d",
  print: "#0d6efd",

  // Colores para secciones
  sectionBasic: "#2196f3",
  sectionTechnical: "#ff9800",
  sectionOrometer: "#4caf50",
  sectionObservations: "#9c27b0",
  sectionComponents: "#f44336",
};

// Exportar para uso global
if (typeof window !== "undefined") {
  window.SISPROMIN_COLORS = colors;
}
