// utils/textHelpers.js
export function createAcronym(text, maxLength = 15) {
  if (text.length <= maxLength) {
    return text
  }

  const skipWords = ["si", "și", "cu", "de", "la", "in", "în", "pe", "din", "pentru"]

  const words = text
    .split(/\s+/)
    .filter((word) => word.length > 0)
    .filter((word) => !skipWords.includes(word.toLowerCase()))

  //   if (words.length === 1) {
  //     return text.substring(0, 6).toUpperCase()
  //   }

  //   if (words.length === 2) {
  //     return (words[0].substring(0, 3) + words[1].substring(0, 3)).toUpperCase()
  //   }

  return words
    .map((word) => word.charAt(0))
    .join("")
    .toUpperCase()
}
