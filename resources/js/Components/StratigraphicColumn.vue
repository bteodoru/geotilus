<template>
  <div ref="visualization" class="stratigraphic-column-container"></div>
</template>

<script>
import * as d3 from "d3"
import { textwrap } from "d3-textwrap"
import { createAcronym } from "@/utils/textHelpers.js"
export default {
  name: "StratigraphicColumn",
  props: {
    strata: { type: Array, required: true },
    granulometricStrata: { type: Array, default: () => [] },
    showGranulometricOverlay: { type: Boolean, default: false },
    width: { type: Number, default: 200 },
    height: { type: Number, default: 600 },
    maxDepth: { type: Number, default: null },
  },
  data() {
    return {
      svg: null,
      column: null,
      boundaries: null,
      yScale: null,
      dragBehavior: null,
    }
  },
  computed: {
    // _computedMaxDepth() {
    //   return this.maxDepth || Math.max(...this.strata.map((l) => +l.depth_to), 0.1)
    // },
    // _sortedStrata() {
    //   return [...this.strata].filter((l) => l.isValid !== false).sort((a, b) => +a.depth_from - +b.depth_from)
    // },
    computedMaxDepth() {
      return this.maxDepth || Math.max(...this.strata.map((l) => +l.depth_to), 0.1)
    },
    sortedStrata() {
      // console.log("🔍 sortedStrata - Input strata:", this.strata.length, "straturi") // DEBUG

      // Logging detailat pentru fiecare strat
      this.strata.forEach((layer, index) => {
        const thickness = parseFloat(layer.depth_to) - parseFloat(layer.depth_from)
        // console.log(`📋 Strat ${index}:`, {
        //   id: layer.id,
        //   soil_type: layer.soil_type,
        //   depth_from: layer.depth_from,
        //   depth_to: layer.depth_to,
        //   isValid: layer.isValid,
        //   thickness: thickness.toFixed(3) + "m",
        // }) // DEBUG
      })

      // Filtrarea cu logging explicit
      const filtered = [...this.strata].filter((layer, index) => {
        const isValidFlag = layer.isValid !== false
        const thickness = parseFloat(layer.depth_to) - parseFloat(layer.depth_from)
        const hasValidThickness = thickness > 0.01 // Minimum 1cm

        const shouldInclude = isValidFlag && hasValidThickness

        // if (!isValidFlag) {
        //   console.log(`❌ Strat ${index} EXCLUS - isValid=${layer.isValid}:`, layer.soil_type) // DEBUG
        // }
        // if (!hasValidThickness) {
        //   console.log(`❌ Strat ${index} EXCLUS - grosime=${thickness.toFixed(3)}m:`, layer.soil_type) // DEBUG
        // }
        // if (shouldInclude) {
        //   console.log(`✅ Strat ${index} INCLUS:`, layer.soil_type) // DEBUG
        // }

        return shouldInclude
      })

      // Sortarea
      const sorted = filtered.sort((a, b) => +a.depth_from - +b.depth_from)

      // console.log(`🎯 sortedStrata - Output: ${sorted.length} straturi (din ${this.strata.length} originale)`) // DEBUG

      return sorted
    },
    sortedGranulometricStrata() {
      // Procesează stratigrafia granulometrică
      if (!this.granulometricStrata || this.granulometricStrata.length === 0) {
        return []
      }

      const filtered = [...this.granulometricStrata].filter((layer) => {
        const thickness = parseFloat(layer.depth_to) - parseFloat(layer.depth_from)
        return thickness > 0.01
      })

      return filtered.sort((a, b) => +a.depth_from - +b.depth_from)
    },
  },
  watch: {
    strata: { handler: "forceRedraw", deep: true },
    width: "forceRedraw",
    height: "forceRedraw",
  },
  mounted() {
    this.initializeD3()
    this.createVisualization()
  },
  methods: {
    updateVisualization() {
      this.createVisualization()
    },
    initializeD3() {
      const margins = { top: 10, right: 10, bottom: 10, left: 10 }
      if (this.svg) this.svg.remove()

      this.svg = d3.select(this.$refs.visualization).append("svg").attr("width", this.width).attr("height", this.height).attr("class", "stratigraphy-visualization")

      // Grupul principal pentru stratigrafia originală
      this.column = this.svg
        .append("g")
        .attr("class", "stratigraphy-column")
        .attr("transform", `translate(${this.width / 12},0)`)

      // Grupul pentru overlay-ul granulometric (poziționat lateral)
      this.granulometricOverlay = this.svg
        .append("g")
        .attr("class", "granulometric-overlay")
        .attr("transform", `translate(${(this.width * 7) / 12},0)`)

      // Grupul pentru handle-urile de drag (doar pentru stratigrafia originală)
      this.boundaries = this.svg.append("g").attr("class", "boundary-handles")

      this.yScale = d3
        .scaleLinear()
        .domain([0, this.computedMaxDepth])
        .range([margins.top, this.height - margins.bottom])

      this.dragBehavior = d3.drag().on("start", this.onDragStart).on("drag", this.onDrag).on("end", this.onDragEnd)
    },

    _initializeD3() {
      if (this.svg) this.svg.remove()
      this.svg = d3.select(this.$refs.visualization).append("svg").attr("width", this.width).attr("height", this.height).attr("class", "stratigraphy-visualization")
      this.column = this.svg
        .append("g")
        .attr("class", "stratigraphy-column")
        .attr("transform", `translate(${this.width / 4},0)`)
      this.boundaries = this.svg.append("g").attr("class", "boundary-handles")
      this.yScale = d3.scaleLinear().domain([0, this.computedMaxDepth]).range([0, this.height])
      this.dragBehavior = d3.drag().on("start", this.onDragStart).on("drag", this.onDrag).on("end", this.onDragEnd)
    },
    createVisualization() {
      if (!this.svg) return

      this.yScale.domain([0, this.computedMaxDepth])

      // Creează stratigrafia originală (colonia din stânga)
      this.createOriginalStratigraphy()

      // Creează overlay-ul granulometric (coloana din dreapta)
      this.updateGranulometricOverlay()

      // Actualizează boundaries și axa Y
      this.updateBoundaries()
      this.drawYAxis()
    },

    createOriginalStratigraphy() {
      const columnWidth = (this.width * 2) / 12 // O treime din lățime pentru coloana principală

      const layers = this.column.selectAll("g.layer").data(this.sortedStrata, (d) => `${d.depth_from}-${d.depth_to}-${d.soil_type}`)

      layers.exit().remove()

      const enter = layers
        .enter()
        .append("g")
        .attr("class", "layer")
        .attr("data-soil-type", (d) => d.soil_type)

      // Dreptunghiul stratului
      enter
        .append("rect")
        .attr("width", columnWidth)
        .attr("stroke", "#475569")
        .attr("stroke-width", 1)
        .attr("fill", (d) => this.getSoilTypeColor(d.soil_type))

      // Textul descriptiv
      enter
        .append("text")
        // .attr("text-anchor", "middle")
        // .attr("x", columnWidth / 2)
        .attr("x", columnWidth + 5)
        .attr("fill", "#475569")
        .attr("font-size", "10px")
        .attr("font-weight", "bold")
        .attr("dominant-baseline", "middle")
        // .text((d) => d.soil_type)
        .text((d) => createAcronym(d.soil_type))
        .each(function (d) {
          // Adaugă tooltip doar dacă textul a fost scurtat
          if (d.soil_type.length > 15) {
            d3.select(this).style("cursor", "help").append("title").text(d.soil_type)
          }
        })
      // .call(wrapText, 50)
      // .call(textwrap().bounds({ width: 100, height: 100 }))

      const all = enter.merge(layers)

      all
        .select("rect")
        .attr("y", (d) => this.yScale(+d.depth_from))
        .attr("height", (d) => this.yScale(+d.depth_to) - this.yScale(+d.depth_from))

      all.select("text").attr("y", (d) => this.yScale(+d.depth_from) + (this.yScale(+d.depth_to) - this.yScale(+d.depth_from)) / 2)
      // .call(wrapTextWithYAdjustment, 50)
    },

    updateGranulometricOverlay() {
      function wrapTextWithYAdjustment(text, width, verticalAlign = "middle") {
        text.each(function () {
          const text = d3.select(this)
          const words = text.text().split(/\s+/).reverse()
          let word
          let line = []
          let lineNumber = 0
          const lineHeight = 1.1 // în em
          const originalY = parseFloat(text.attr("y"))
          const dy = parseFloat(text.attr("dy")) || 0

          // Păstrează textul original pentru măsurare
          const originalText = text.text()
          text.text(null)

          // Array pentru a stoca liniile
          const lines = []
          let currentLine = []

          // Creează un tspan temporar pentru măsurare
          let tspan = text.append("tspan").attr("x", text.attr("x")).style("visibility", "hidden")

          while ((word = words.pop())) {
            currentLine.push(word)
            tspan.text(currentLine.join(" "))
            if (tspan.node().getComputedTextLength() > width) {
              currentLine.pop()
              if (currentLine.length > 0) {
                lines.push(currentLine.join(" "))
              }
              currentLine = [word]
            }
          }
          if (currentLine.length > 0) {
            lines.push(currentLine.join(" "))
          }

          // Șterge tspan-ul temporar
          tspan.remove()

          // Calculează ajustarea Y în funcție de numărul de linii
          const totalLines = lines.length
          let yAdjustment = 0

          switch (verticalAlign) {
            case "top":
              yAdjustment = 0
              break
            case "middle":
              yAdjustment = -((totalLines - 1) * lineHeight * 0.5)
              break
            case "bottom":
              yAdjustment = -(totalLines - 1) * lineHeight
              break
          }

          // Adaugă tspan-urile finale cu Y ajustat
          lines.forEach((line, i) => {
            text
              .append("tspan")
              .attr("x", text.attr("x"))
              .attr("y", originalY)
              .attr("dy", yAdjustment + i * lineHeight + dy + "em")
              .text(line)
          })
        })
      }
      if (!this.svg || !this.showGranulometricOverlay || this.sortedGranulometricStrata.length === 0) {
        // Ascunde overlay-ul
        this.granulometricOverlay.selectAll("*").remove()
        return
      }

      const overlayWidth = this.width / 6 // Un sfert din lățime pentru overlay

      const overlayLayers = this.granulometricOverlay.selectAll("g.granulo-layer").data(this.sortedGranulometricStrata, (d) => `granulo-${d.depth_from}-${d.depth_to}-${d.soil_type}`)

      overlayLayers.exit().remove()

      const enter = overlayLayers
        .enter()
        .append("g")
        .attr("class", "granulo-layer")
        .attr("data-soil-type", (d) => d.soil_type)

      // Dreptunghiul overlay-ului (semi-transparent)
      enter
        .append("rect")
        .attr("width", overlayWidth)
        .attr("stroke", "#6b7280")
        .attr("stroke-width", 1)
        .attr("stroke-dasharray", "5,3") // Linie punctată pentru diferențiere
        .attr("fill", (d) => this.getSoilTypeColor(d.soil_type))
        .attr("fill-opacity", 0.5) // Semi-transparent

      // Textul overlay-ului
      enter
        .append("text")
        .attr("x", -overlayWidth)
        // .attr("x", overlayWidth + 5)
        // .attr("fill", "#e74c3c")
        .attr("fill", "#475569")
        .attr("font-size", "10px")
        .attr("font-weight", "bold")
        .attr("dominant-baseline", "middle")
        .attr("fill-opacity", 0.5) // Semi-transparent

        // .text((d) => `${d.soil_type}`) // (G) pentru Granulometric
        // .text((d) => `${d.soil_type} (G)`) // (G) pentru Granulometric
        // .call(wrapTextWithYAdjustment, 50)
        .text((d) => createAcronym(d.soil_type))
        .each(function (d) {
          // Adaugă tooltip doar dacă textul a fost scurtat
          if (d.soil_type.length > 15) {
            d3.select(this).style("cursor", "help").append("title").text(d.soil_type)
          }
        })

      const all = enter.merge(overlayLayers)

      all
        .select("rect")
        .attr("y", (d) => this.yScale(+d.depth_from))
        .attr("height", (d) => this.yScale(+d.depth_to) - this.yScale(+d.depth_from))

      all.select("text").attr("y", (d) => this.yScale(+d.depth_from) + (this.yScale(+d.depth_to) - this.yScale(+d.depth_from)) / 2)
      // .call(wrapTextWithYAdjustment, 50)
    },

    toggleOverlay() {
      this.$emit("toggle-granulometric-overlay")
    },

    _updateBoundaries() {
      // Păstrează logica existentă pentru boundaries - doar pentru stratigrafia originală
      const data = this.sortedStrata
        .map((l, i) => ({
          depth: +l.depth_to,
          layerIndex: i,
          fixed: i === this.sortedStrata.length - 1 && +l.depth_to >= this.computedMaxDepth,
        }))
        .filter((b) => b.depth >= 0)

      data.unshift({ depth: 0, layerIndex: -1, fixed: true })
      data.sort((a, b) => a.depth - b.depth)

      const handles = this.boundaries.selectAll("g.boundary-handle").data(data, (d) => `b-${d.depth}`)

      handles.exit().remove()

      const enter = handles
        .enter()
        .append("g")
        .attr("class", "boundary-handle")
        .attr("data-layer-index", (d) => d.layerIndex)

      // Linia de boundary (extinsă peste ambele coloane)
      enter
        .append("line")
        .attr("x1", 0)
        .attr("x2", this.width * 0.9) // Se extinde peste ambele coloane
        .attr("stroke", "#666")
        .attr("stroke-width", 1)
        .attr("stroke-dasharray", (d) => (d.fixed ? "0" : "3,3"))

      // Handle-ul de drag (doar pe coloana principală)
      enter
        .filter((d) => !d.fixed)
        .append("circle")
        .attr("cx", this.width / 3 / 2) // Centrat pe coloana principală
        .attr("r", 6)
        .attr("fill", "#fff")
        .attr("stroke", "#333")
        .attr("stroke-width", 2)
        .attr("cursor", "ns-resize")
        .call(this.dragBehavior)

      // Label-ul cu adâncimea
      enter
        .append("text")
        .attr("x", 5)
        .attr("font-size", "10px")
        .attr("dominant-baseline", "top")
        .attr("y", -2)
        .text((d) => `${d.depth.toFixed(2)}m`)

      const all = enter.merge(handles)

      all.attr("transform", (d) => `translate(0,${this.yScale(d.depth)})`)
      all.select("text").text((d) => `${d.depth.toFixed(2)}m`)
    },

    drawYAxis() {
      this.svg.selectAll(".y-axis").remove()

      const axis = d3
        .axisRight(this.yScale)
        .ticks(10)
        .tickFormat((d) => `${d}m`)

      this.svg
        .append("g")
        .attr("class", "y-axis")
        .attr("transform", `translate(${this.width - 30},0)`)
        .call(axis)
    },

    _createVisualization() {
      if (!this.svg) return
      this.yScale.domain([0, this.computedMaxDepth])
      const layers = this.column.selectAll("g.layer").data(this.sortedStrata, (d) => `${d.depth_from}-${d.depth_to}-${d.soil_type}`)
      layers.exit().remove()
      const enter = layers
        .enter()
        .append("g")
        .attr("class", "layer")
        .attr("data-soil-type", (d) => d.soil_type)
      enter
        .append("rect")
        .attr("width", this.width / 2)
        .attr("stroke", "#333")
        .attr("stroke-width", 1)
        .attr("fill", (d) => this.getSoilTypeColor(d.soil_type))
      enter
        .append("text")
        .attr("x", this.width / 2 + 10)
        .attr("fill", "#333")
        .attr("font-size", "12px")
        .attr("dominant-baseline", "middle")
        .text((d) => d.soil_type)
      const all = enter.merge(layers)
      all
        .select("rect")
        .attr("y", (d) => this.yScale(+d.depth_from))
        .attr("height", (d) => this.yScale(+d.depth_to) - this.yScale(+d.depth_from))
      all.select("text").attr("y", (d) => this.yScale(+d.depth_from) + (this.yScale(+d.depth_to) - this.yScale(+d.depth_from)) / 2)
      this.updateBoundaries()
      this.drawYAxis()
    },
    updateBoundaries() {
      const data = this.sortedStrata.map((l, i) => ({ depth: +l.depth_to, layerIndex: i, fixed: i === this.sortedStrata.length - 1 && +l.depth_to >= this.computedMaxDepth })).filter((b) => b.depth >= 0)
      data.unshift({ depth: 0, layerIndex: -1, fixed: true })
      data.sort((a, b) => a.depth - b.depth)
      const handles = this.boundaries.selectAll("g.boundary-handle").data(data, (d) => `b-${d.depth}`)
      handles.exit().remove()
      const enter = handles
        .enter()
        .append("g")
        .attr("class", "boundary-handle")
        .attr("data-layer-index", (d) => d.layerIndex)
      // enter
      //   .append("line")
      //   .attr("x1", 0)
      //   .attr("x2", this.width)
      //   .attr("stroke", "#666")
      //   .attr("stroke-width", 1)
      //   .attr("stroke-dasharray", (d) => (d.fixed ? "0" : "3,3"))
      enter
        .filter((d) => !d.fixed)
        .append("circle")
        .attr("cx", this.width / 12 + this.width / 12) // Centrat pe coloana principală
        .attr("r", 5)
        .attr("fill", "#fff")
        .attr("stroke", "#475569")
        .attr("stroke-width", 2)
        .attr("cursor", "ns-resize")
        .call(this.dragBehavior)
      enter
        .append("text")
        .attr("x", 5)
        .attr("font-size", "10px")
        .attr("dominant-baseline", "middle")
        // .attr("y", -2)
        .text((d) => `${d.depth.toFixed(2)}m`)
      const all = enter.merge(handles)
      all.attr("transform", (d) => `translate(0,${this.yScale(d.depth)})`)
      // all.select("text").text((d) => `${d.depth.toFixed(2)}m`)
    },
    drawYAxis() {
      this.svg.selectAll(".y-axis").remove()
      const axis = d3
        .axisRight(this.yScale)
        .ticks(10)
        .tickFormat((d) => `${d}m`)
      this.svg
        .append("g")
        .attr("class", "y-axis")
        .attr("transform", `translate(${this.width - 30},0)`)
        .call(axis)
    },
    onDragStart(event, d) {
      d.originalDepth = d.depth
      this.svg.append("line").attr("class", "drag-guide").attr("x1", 0).attr("x2", this.width).attr("y1", this.yScale(d.depth)).attr("y2", this.yScale(d.depth)).attr("stroke", "#007bff").attr("stroke-dasharray", "4,4")
    },

    onDrag(event, d) {
      // console.log("onDrag apelat!", d) // DEBUG

      const idx = d.layerIndex
      if (idx < 0) return // Ignorăm limita de la suprafață (depth=0)

      // MODIFICARE SIMPLĂ: Să vedem constrângerile actuale
      // console.log("Layer index:", idx) // DEBUG
      // console.log("Straturi sortate:", this.sortedStrata) // DEBUG

      // Determinăm constrângerile globale
      const globalMinDepth = +this.sortedStrata[0].depth_from // depth_from al primului strat
      const globalMaxDepth = +this.sortedStrata[this.sortedStrata.length - 1].depth_to // depth_to al ultimului strat

      // console.log("Constrângeri globale:", globalMinDepth, "la", globalMaxDepth) // DEBUG

      // În loc de constrângeri locale, folosim constrângeri globale
      const minD = globalMinDepth
      const maxD = globalMaxDepth

      // console.log("Min/Max aplicat:", minD, maxD) // DEBUG

      // Obținem coordonata Y relativă la SVG
      const [_, relativeY] = d3.pointer(event, this.svg.node())
      let depth = this.yScale.invert(relativeY)

      // console.log("Adâncime calculată:", depth) // DEBUG

      // MAGNETIZARE: Rotunjim la cel mai apropiat multiplu de 10cm (0.1m)
      const snapIncrement = 0.1 // 10 cm
      depth = Math.round(depth / snapIncrement) * snapIncrement

      // Aplicăm constrângerile
      depth = Math.max(minD, Math.min(maxD, depth))
      d.depth = +depth.toFixed(1) // Păstrăm doar 1 zecimală

      // console.log("Adâncime finală:", d.depth) // DEBUG

      // Actualizăm linia de drag-guide
      const dragGuide = this.svg.select(".drag-guide")
      dragGuide.attr("y1", this.yScale(d.depth)).attr("y2", this.yScale(d.depth)).attr("cursor", "ns-resize")

      // Adaugă/actualizează textul pe linia de drag-guide
      let dragText = this.svg.select(".drag-guide-text")

      if (dragText.empty()) {
        // Creează textul dacă nu există
        dragText = this.svg
          .append("text")
          .attr("class", "drag-guide-text")
          .attr("font-size", "10px")
          .attr("dominant-baseline", "bottom") // Poziționează deasupra liniei
          .attr("fill", "#007bff")
          .attr("font-weight", "bold")
          .style("pointer-events", "none") // Nu interfere cu drag-ul
      }

      // Actualizează poziția și textul
      dragText
        .attr("x", 5)
        .attr("y", this.yScale(d.depth) - 3) // 3px deasupra liniei
        .text(`${d.depth.toFixed(2)}m`)

      // Actualizăm vizualizarea
      this.createVisualization()
      // Actualizăm vizualizarea
      // this.createVisualization()
      // this.svg.select(".drag-guide").attr("y1", this.yScale(d.depth)).attr("y2", this.yScale(d.depth))
    },

    onDragEnd(event, d) {
      // console.log("onDragEnd apelat!", d) // DEBUG

      this.svg.select(".drag-guide").remove()
      if (Math.abs(d.depth - d.originalDepth) < 0.01) {
        // console.log("Nicio modificare - depth rămâne la:", d.depth) // DEBUG
        return
      }

      // console.log("Modificare detectată:", d.originalDepth, "->", d.depth) // DEBUG

      const idx = d.layerIndex
      const newDepth = d.depth
      const originalDepth = d.originalDepth

      // Creăm o copie completă a straturilor pentru a efectua modificările
      const updatedStrata = JSON.parse(JSON.stringify(this.sortedStrata))
      const affectedLayers = []

      // console.log("Straturile înainte de modificare:", updatedStrata) // DEBUG

      // CAZA 1: Mutăm limita în jos (newDepth > originalDepth)
      if (newDepth > originalDepth) {
        // console.log("Mutăm limita în jos") // DEBUG

        // PRIMUL PAS: Actualizez stratul curent (se mărește)
        updatedStrata[idx].depth_to = newDepth
        affectedLayers.push({ index: idx, layer: { ...updatedStrata[idx] } })
        // console.log(`Stratul curent ${idx} mărit la depth_to: ${newDepth}`) // DEBUG

        // AL DOILEA PAS: Verific straturile următoare care pot fi afectate
        for (let i = idx + 1; i < updatedStrata.length; i++) {
          const layer = updatedStrata[i]

          if (newDepth <= layer.depth_from) {
            // Noua limită nu afectează acest strat - ne oprim
            // console.log(`Stratul ${i} nu este afectat de mișcarea în jos`) // DEBUG
            break
          } else if (newDepth >= layer.depth_to) {
            // Noua limită depășește complet acest strat - îl "consumăm"
            // console.log(`Stratul ${i} este complet consumat de mișcarea în jos`) // DEBUG
            updatedStrata[i].isValid = false
            affectedLayers.push({
              index: i,
              layer: {
                ...updatedStrata[i],
                isValid: false, // Forțez explicit isValid: false în payload
              },
            })
          } else {
            // Noua limită cade în interiorul acestui strat - îl trunchiez
            // console.log(`Stratul ${i} este trunchiat în jos de la ${layer.depth_from} la ${newDepth}`) // DEBUG
            updatedStrata[i].depth_from = newDepth
            affectedLayers.push({ index: i, layer: { ...updatedStrata[i] } })
            break // Ne oprim aici
          }
        }
      }

      // CAZA 2: Mutăm limita în sus (newDepth < originalDepth)
      else {
        // console.log("Mutăm limita în sus") // DEBUG

        // Verific dacă mișcarea ar duce la grosime negativă pentru stratul curent
        const currentDepthFrom = parseFloat(updatedStrata[idx].depth_from)

        if (newDepth <= currentDepthFrom) {
          // console.log(`Mișcarea ar duce la grosime negativă (${newDepth} <= ${currentDepthFrom}) - invalidez stratul curent`) // DEBUG

          // Invalidez stratul curent
          updatedStrata[idx].isValid = false
          affectedLayers.push({
            index: idx,
            layer: {
              ...updatedStrata[idx],
              isValid: false, // Forțez explicit isValid: false în payload
            },
          })

          // Extind stratul anterior pentru a umple golul
          if (idx > 0) {
            updatedStrata[idx - 1].depth_to = newDepth
            affectedLayers.push({ index: idx - 1, layer: { ...updatedStrata[idx - 1] } })
            // console.log(`Stratul anterior ${idx - 1} extins la depth_to: ${newDepth}`) // DEBUG
          }

          // Actualizez stratul următor
          if (idx + 1 < updatedStrata.length) {
            updatedStrata[idx + 1].depth_from = newDepth
            affectedLayers.push({ index: idx + 1, layer: { ...updatedStrata[idx + 1] } })
            // console.log(`Stratul următor ${idx + 1} actualizat cu depth_from: ${newDepth}`) // DEBUG
          }
        } else {
          // Mișcarea este validă - stratul curent se micșorează normal
          // console.log("Mișcarea este validă - micșorez stratul curent") // DEBUG

          // PRIMUL PAS: Actualizez stratul curent (se micșorează)
          updatedStrata[idx].depth_to = newDepth
          affectedLayers.push({ index: idx, layer: { ...updatedStrata[idx] } })
          // console.log(`Stratul curent ${idx} micșorat la depth_to: ${newDepth}`) // DEBUG

          // AL DOILEA PAS: Actualizez stratul următor (se mărește)
          if (idx + 1 < updatedStrata.length) {
            updatedStrata[idx + 1].depth_from = newDepth
            affectedLayers.push({ index: idx + 1, layer: { ...updatedStrata[idx + 1] } })
            // console.log(`Stratul următor ${idx + 1} mărit cu depth_from: ${newDepth}`) // DEBUG
          }
        }

        // AL TREILEA PAS: Verific straturile intermediare care pot fi "consumate"
        // (doar dacă nu am invalidat stratul curent)
        if (updatedStrata[idx].isValid !== false) {
          for (let i = idx + 2; i < updatedStrata.length; i++) {
            const layer = updatedStrata[i]

            if (newDepth >= layer.depth_to) {
              // Noua limită depășește complet acest strat - îl "consumăm"
              // console.log(`Stratul ${i} este complet consumat de mișcarea în sus`) // DEBUG
              updatedStrata[i].isValid = false
              affectedLayers.push({
                index: i,
                layer: {
                  ...updatedStrata[i],
                  isValid: false, // Forțez explicit isValid: false în payload
                },
              })
            } else if (newDepth > layer.depth_from && newDepth < layer.depth_to) {
              // Noua limită cade în interiorul acestui strat - îl trunchiez
              // console.log(`Stratul ${i} este trunchiat în sus de la ${layer.depth_from} la ${newDepth}`) // DEBUG
              updatedStrata[i].depth_from = newDepth
              affectedLayers.push({ index: i, layer: { ...updatedStrata[i] } })
              break // Ne oprim aici
            } else {
              // Acest strat nu este afectat - ne oprim
              // console.log(`Stratul ${i} nu este afectat de mișcarea în sus`) // DEBUG
              break
            }
          }
        }
      }

      // Validăm toate straturile pentru a ne asigura că au grosimi pozitive
      for (let i = 0; i < updatedStrata.length; i++) {
        const layer = updatedStrata[i]
        const thickness = parseFloat(layer.depth_to) - parseFloat(layer.depth_from)

        if (thickness <= 0.01) {
          // Sub 1cm considerăm invalid
          // console.log(`Stratul ${i} invalidat din cauza grosimii: ${thickness.toFixed(3)}m`) // DEBUG
          updatedStrata[i].isValid = false

          // Adăugăm la affected layers dacă nu este deja acolo
          if (!affectedLayers.find((al) => al.index === i)) {
            affectedLayers.push({
              index: i,
              layer: {
                ...updatedStrata[i],
                isValid: false, // Forțez explicit isValid: false în payload
              },
            })
          }
        }
      }

      // console.log("Straturile după modificare:", updatedStrata) // DEBUG
      // console.log("Straturi afectate:", affectedLayers) // DEBUG

      // Construim payload-ul pentru emit
      const payload = {
        type: "complex", // Nou tip pentru a distinge de modificările simple
        primaryChange: {
          index: idx,
          layer: { ...updatedStrata[idx] },
        },
        affectedLayers: affectedLayers,
        newDepth: newDepth,
        direction: newDepth > originalDepth ? "down" : "up",
      }

      // console.log("Payload complex emis:", payload) // DEBUG
      this.$emit("boundary-moved", payload)
    },
    _onDrag(event, d) {
      const idx = d.layerIndex
      if (idx < 0) return
      const minD = +this.sortedStrata[idx].depth_from + 0.1
      const next = this.sortedStrata[idx + 1]
      const maxD = next ? +next.depth_to - 0.1 : this.computedMaxDepth

      // Use D3's pointer function to get coordinates relative to the SVG
      const [_, relativeY] = d3.pointer(event, this.svg.node())

      let depth = this.yScale.invert(relativeY)
      depth = Math.max(minD, Math.min(maxD, depth))
      d.depth = +depth.toFixed(2)
      this.createVisualization()
      this.svg.select(".drag-guide").attr("y1", this.yScale(d.depth)).attr("y2", this.yScale(d.depth))
    },
    onDrag_(event, d) {
      const idx = d.layerIndex
      if (idx < 0) return
      const minD = +this.sortedStrata[idx].depth_from + 0.1
      const next = this.sortedStrata[idx + 1]
      const maxD = next ? +next.depth_to - 0.1 : this.computedMaxDepth
      // use pointer to get accurate y relative to svg
      const [, newY] = d3.pointer(event, this.svg.node())
      let depth = this.yScale.invert(newY)
      depth = Math.max(minD, Math.min(maxD, depth))
      d.depth = +depth.toFixed(2)
      this.createVisualization()
      this.svg.select(".drag-guide").attr("y1", this.yScale(d.depth)).attr("y2", this.yScale(d.depth))
    },
    onDrag__(event, d) {
      const idx = d.layerIndex
      if (idx < 0) return
      const minD = +this.sortedStrata[idx].depth_from + 0.1
      const next = this.sortedStrata[idx + 1]
      const maxD = next ? +next.depth_to - 0.1 : this.computedMaxDepth

      // Get the SVG's position relative to the viewport
      const svgNode = this.svg.node()
      const svgRect = svgNode.getBoundingClientRect()

      // Calculate y-coordinate relative to the SVG
      const relativeY = event.y - svgRect.top

      // Now convert to depth
      let depth = this.yScale.invert(relativeY)
      depth = Math.max(minD, Math.min(maxD, depth))
      d.depth = +depth.toFixed(2)
      this.createVisualization()
      this.svg.select(".drag-guide").attr("y1", this.yScale(d.depth)).attr("y2", this.yScale(d.depth))
    },
    _onDragEnd(event, d) {
      this.svg.select(".drag-guide").remove()
      if (d.depth === d.originalDepth) return
      const idx = d.layerIndex
      const payload = { currentLayer: { ...this.sortedStrata[idx], depth_to: d.depth }, indices: { currentLayerIndex: idx } }
      if (idx < this.sortedStrata.length - 1) {
        payload.nextLayer = { ...this.sortedStrata[idx + 1], depth_from: d.depth }
        payload.indices.nextLayerIndex = idx + 1
      }
      this.$emit("boundary-moved", payload)
    },
    forceRedraw() {
      this.initializeD3()
      this.createVisualization()
    },
    getSoilTypeColor(type) {
      const colors = {
        "Argilă grasă": "#fad7d5",
        Argilă: "#fff498",
        "Argilă prafoasă": "#e7ac7c",
        "Argilă prafoasă nisipoasă": "#f5f5f4",
        "Argila nisipoasă": "#c6d291",
        Praf: "#ee9479",
        "Praf argilos": "#e5e3f0",
        "Praf nisipos argilos": "#ffe292",
        "Praf nisipos": "#b2a9d2",
        Nisip: "#83c097",
        "Nisip argilos": "#f8baa3",
        "Nisip prăfos": "#d0e7d2",
      }
      // const colors = { "Nisip argilos": "#d9c994", "Argilâ grasă": "#a65628", "Praf nisipos argilos": "#e6ac93", "Nisip prăfos": "#f4e992", "Argila nisipoasă": "#bc8f8f" }
      return colors[type] || "#ccc"
    },
  },
}
</script>

<style scoped>
.stratigraphic-column-container {
  position: relative;
  display: inline-block;
  margin: 0;
  padding: 0;
}
.stratigraphy-visualization {
  background-color: #f9f9f9;
  border: 1px solid #ddd;
  border-radius: 4px;
  padding: 16px;
}
.boundary-handle circle:hover {
  fill: #eee;
  stroke-width: 3px;
}
.y-axis .domain {
  stroke: #666;
}
.y-axis .tick line {
  stroke: #999;
}
.y-axis text {
  font-size: 10px;
  fill: #666;
}
</style>
