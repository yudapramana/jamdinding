<template>
  <div>
    <canvas ref="pdfCanvas"></canvas>
    <div class="flex gap-4 mt-4">
      <button @click="goToPrevPage" :disabled="pageNumber <= 1">Previous</button>
      <span>Page {{ pageNumber }} of {{ totalPages }}</span>
      <button @click="goToNextPage" :disabled="pageNumber >= totalPages">Next</button>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'
import * as pdfjsLib from 'pdfjs-dist'
import pdfjsWorker from 'pdfjs-dist/build/pdf.worker.min.mjs'

// Set the workerSrc globally
pdfjsLib.GlobalWorkerOptions.workerSrc = pdfjsWorker

const props = defineProps({
  url: {
    type: String,
    required: true,
  },
})

const pdfDoc = ref(null)
const pageNumber = ref(1)
const totalPages = ref(0)
const pdfCanvas = ref(null)

const renderPage = async (num) => {
  const page = await pdfDoc.value.getPage(num)
  const viewport = page.getViewport({ scale: 1.5 })
  const canvas = pdfCanvas.value
  const context = canvas.getContext('2d')

  canvas.height = viewport.height
  canvas.width = viewport.width

  const renderContext = {
    canvasContext: context,
    viewport: viewport,
  }

  await page.render(renderContext).promise
}

const loadPdf = async () => {
  pdfDoc.value = await pdfjsLib.getDocument(props.url).promise
  totalPages.value = pdfDoc.value.numPages
  await renderPage(pageNumber.value)
}

const goToPrevPage = async () => {
  if (pageNumber.value <= 1) return
  pageNumber.value--
  await renderPage(pageNumber.value)
}

const goToNextPage = async () => {
  if (pageNumber.value >= totalPages.value) return
  pageNumber.value++
  await renderPage(pageNumber.value)
}

onMounted(loadPdf)
</script>

<style scoped>
canvas {
  border: 1px solid #ccc;
  max-width: 100%;
}
</style>
