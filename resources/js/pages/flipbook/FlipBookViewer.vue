<template>
  <div class="book-viewer">
    <div class="book">
      <div
        v-for="(page, index) in pages"
        :key="index"
        class="page"
        :class="{ flipped: index < currentPage }"
        @click="flipPage(index)"
      >
        <img :src="page" />
      </div>
    </div>
    <div class="controls">
      <button @click="prevPage" :disabled="currentPage === 0">Previous</button>
      <button @click="nextPage" :disabled="currentPage >= pages.length">Next</button>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import * as pdfjsLib from 'pdfjs-dist'

pdfjsLib.GlobalWorkerOptions.workerSrc = `https://cdnjs.cloudflare.com/ajax/libs/pdf.js/${pdfjsLib.version}/pdf.worker.min.js`

const pdfUrl = '/sample.pdf' // Path to your PDF
const pages = ref([])
const currentPage = ref(0)

async function renderPDF() {
  const pdf = await pdfjsLib.getDocument(pdfUrl).promise
  for (let i = 1; i <= pdf.numPages; i++) {
    const page = await pdf.getPage(i)
    const viewport = page.getViewport({ scale: 1.5 })
    const canvas = document.createElement('canvas')
    canvas.width = viewport.width
    canvas.height = viewport.height
    const ctx = canvas.getContext('2d')
    await page.render({ canvasContext: ctx, viewport }).promise
    pages.value.push(canvas.toDataURL())
  }
}

function flipPage(index) {
  if (index === currentPage.value) {
    currentPage.value++
  }
}

function prevPage() {
  if (currentPage.value > 0) currentPage.value--
}

function nextPage() {
  if (currentPage.value < pages.value.length) currentPage.value++
}

onMounted(() => {
  renderPDF()
})
</script>

<style scoped>
.book-viewer {
  display: flex;
  flex-direction: column;
  align-items: center;
}

.book {
  position: relative;
  width: 800px;
  height: 600px;
  perspective: 2000px;
}

.page {
  position: absolute;
  width: 100%;
  height: 100%;
  transform-origin: left center;
  transition: transform 0.6s;
  backface-visibility: hidden;
  border: 1px solid #ccc;
  box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
}

.page img {
  width: 100%;
  height: 100%;
  object-fit: contain;
}

.page.flipped {
  transform: rotateY(-180deg);
  z-index: 0;
}

.page:not(.flipped) {
  z-index: 1;
}

.controls {
  margin-top: 20px;
}

button {
  padding: 8px 16px;
  margin: 0 10px;
}
</style>
