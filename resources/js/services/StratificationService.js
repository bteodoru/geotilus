// services/StratificationService.js
export default {
  /**
   * Procesează straturile pentru a elimina suprapunerile și a combina straturile adiacente
   */
  processStrata(strata) {
    // Verifică dacă avem straturi de procesat
    if (!strata || strata.length === 0) {
      return []
    }

    // Convertim și curățăm datele de intrare
    const cleanedStrata = strata.map((layer) => ({
      soil_type: layer.soil_type,
      depth_from: parseFloat(layer.depth_from),
      depth_to: parseFloat(layer.depth_to),
      note: layer.note || null,
      isValid: layer.isValid !== undefined ? layer.isValid : true,
    }))

    // Sortăm straturile după adâncime
    const sortedStrata = [...cleanedStrata].sort((a, b) => a.depth_from - b.depth_from)

    // Generăm toate limitele de adâncime
    const boundaries = []
    sortedStrata.forEach((layer) => {
      boundaries.push(layer.depth_from)
      boundaries.push(layer.depth_to)
    })

    // Eliminăm duplicatele și sortăm
    const uniqueBoundaries = [...new Set(boundaries)].sort((a, b) => a - b)

    // Creăm segmente între fiecare pereche de limite
    const segments = []
    for (let i = 0; i < uniqueBoundaries.length - 1; i++) {
      const start = uniqueBoundaries[i]
      const end = uniqueBoundaries[i + 1]

      // Ignoră segmentele zero
      if (Math.abs(end - start) < 0.0001) continue

      // Găsește straturile care acoperă acest segment
      const coveringLayers = sortedStrata
        .map((layer, index) => ({
          index,
          layer,
          priority: index, // Straturile mai recente au prioritate mai mare
        }))
        .filter((item) => {
          const layer = item.layer
          return layer.depth_from <= start && layer.depth_to >= end
        })

      // Sortează după prioritate
      coveringLayers.sort((a, b) => b.priority - a.priority)

      // Folosește stratul cu cea mai mare prioritate
      if (coveringLayers.length > 0) {
        const topLayer = coveringLayers[0].layer
        segments.push({
          depth_from: start,
          depth_to: end,
          soil_type: topLayer.soil_type,
          note: topLayer.note,
          isValid: topLayer.isValid,
        })
      }
    }

    // Combină segmentele adiacente cu același tip de sol
    const result = []
    let current = null

    segments.forEach((segment) => {
      if (current === null) {
        current = { ...segment }
      } else if (current.soil_type === segment.soil_type && Math.abs(current.depth_to - segment.depth_from) < 0.0001) {
        // Combină segmentele
        current.depth_to = segment.depth_to
      } else {
        // Adaugă la rezultat și începe un nou segment
        result.push({ ...current })
        current = { ...segment }
      }
    })

    // Adaugă ultimul segment
    if (current !== null) {
      result.push({ ...current })
    }

    return result
  },

  /**
   * Validează straturile și returnează erori dacă există
   */
  validateStrata(strata) {
    if (!strata || strata.length === 0) {
      return { valid: false, message: "Nu există straturi definite." }
    }

    // Verifică grosimile
    const invalidLayers = strata.filter((layer) => parseFloat(layer.depth_to) <= parseFloat(layer.depth_from))

    if (invalidLayers.length > 0) {
      return {
        valid: false,
        message: "Există straturi cu grosime negativă sau zero.",
        invalidLayers,
      }
    }

    return { valid: true }
  },
}
