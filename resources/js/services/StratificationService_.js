// StratificationService.js
export default {
  /**
   * Procesează straturile pentru a elimina suprapunerile și a combina straturile adiacente
   * de același tip de sol.
   */
  processStrata(strata) {
    // Asigură-te că straturile sunt sortate după adâncimea de început
    const sortedStrata = [...strata].sort((a, b) => a.depth_from - b.depth_from)

    // Pasul 1: Creează toate limitele de adâncime posibile
    const boundaries = []
    sortedStrata.forEach((layer) => {
      boundaries.push(parseFloat(layer.depth_from))
      boundaries.push(parseFloat(layer.depth_to))
    })

    // Elimină duplicatele și sortează limitele
    const uniqueBoundaries = [...new Set(boundaries)].sort((a, b) => a - b)

    // Pasul 2: Creează segmente între fiecare limită
    const segments = []
    for (let i = 0; i < uniqueBoundaries.length - 1; i++) {
      const start = uniqueBoundaries[i]
      const end = uniqueBoundaries[i + 1]

      // Ignoră segmentele cu lungime zero
      if (Math.abs(end - start) < 0.0001) {
        continue
      }

      // Găsește toate straturile care acoperă acest segment
      const coveringLayers = sortedStrata
        .map((layer, index) => ({
          index,
          layer,
          priority: index, // Prioritatea crește cu indexul (straturile de mai jos au prioritate mai mare)
        }))
        .filter((item) => {
          const layer = item.layer
          return parseFloat(layer.depth_from) <= start && parseFloat(layer.depth_to) >= end
        })

      // Sortează straturile după prioritate
      coveringLayers.sort((a, b) => b.priority - a.priority)

      // Ia stratul cu cea mai mare prioritate (dacă există)
      if (coveringLayers.length > 0) {
        const topLayer = coveringLayers[0].layer
        segments.push({
          depth_from: start,
          depth_to: end,
          soil_type: topLayer.soil_type,
          note: topLayer.note || null,
          isValid: true,
        })
      }
    }

    // Pasul 3: Combină segmentele adiacente cu același tip de sol
    const result = []
    let current = null

    segments.forEach((segment) => {
      if (current === null) {
        current = { ...segment }
      } else if (current.soil_type === segment.soil_type && Math.abs(current.depth_to - segment.depth_from) < 0.0001) {
        // Combină acest segment cu cel curent
        current.depth_to = segment.depth_to
      } else {
        // Adaugă segmentul curent la rezultat și începe unul nou
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
   * Validează și corectează un set de straturi
   */
  validateStrata(strata) {
    // Verifică dacă există cel puțin un strat
    if (!strata || strata.length === 0) {
      return { isValid: false, error: "Nu există straturi definite." }
    }

    // Verifică grosimile straturilor
    const invalidLayers = strata.filter((layer) => parseFloat(layer.depth_to) <= parseFloat(layer.depth_from))

    if (invalidLayers.length > 0) {
      return {
        isValid: false,
        error: "Există straturi cu grosime negativă sau zero.",
        invalidLayers,
      }
    }

    // Verifică dacă tipurile de sol sunt definite
    const missingTypes = strata.filter((layer) => !layer.soil_type)

    if (missingTypes.length > 0) {
      return {
        isValid: false,
        error: "Există straturi fără tip de sol definit.",
        missingTypes,
      }
    }

    return { isValid: true }
  },
}
