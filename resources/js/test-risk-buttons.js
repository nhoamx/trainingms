// Script para testear la funcionalidad de RiskActionButtons

// Datos simulados con formato similar al que estamos recibiendo
const mockData = [
  {
    dimension: "Condiciones peligrosas e inseguras",
    nivel_riesgo: "Nulo",
    conteo: 63,
    personal: ["0001", "0002", "0003"]
  },
  {
    dimension: "Condiciones peligrosas e inseguras",
    nivel_riesgo: "Bajo",
    conteo: 17,
    personal: ["0004", "0005"]
  },
  {
    // Misma dimensión con otro nivel de riesgo
    dimension: "Condiciones peligrosas e inseguras",
    nivel_riesgo: "Nulo", // Este caso es el importante para probar la concatenación
    conteo: 10,
    personal: ["0006", "0007"]
  }
];

// Simulación de la función de procesamiento
function processPersonalData(rawData, itemName) {
  const riskGroups = {
    'Nulo': [],
    'Bajo': [],
    'Medio': [],
    'Alto': [],
    'Muy Alto': []
  };

  if (rawData && rawData.length > 0) {
    rawData.forEach(item => {
      if (item.dimension === itemName && item.personal && item.nivel_riesgo) {
        if (!riskGroups[item.nivel_riesgo]) {
          riskGroups[item.nivel_riesgo] = [];
        }
        // Esta es la parte clave: concatenar en lugar de reemplazar
        riskGroups[item.nivel_riesgo] = [...riskGroups[item.nivel_riesgo], ...item.personal];
      }
    });
  }

  return riskGroups;
}

// Ejecutar el test
const result = processPersonalData(mockData, "Condiciones peligrosas e inseguras");
console.log("Resultado de procesamiento:");
console.log(JSON.stringify(result, null, 2));

// Verificar si la concatenación funcionó correctamente
console.log("¿Se concatenaron correctamente los IDs para Nulo?", 
  result.Nulo.length === 5 && 
  result.Nulo.includes("0001") && 
  result.Nulo.includes("0006")
);
