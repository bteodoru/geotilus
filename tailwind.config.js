const defaultTheme = require("tailwindcss/defaultTheme")
const colors = require("tailwindcss/colors")
/** @type {import('tailwindcss').Config} */
module.exports = {
  content: ["./vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php", "./vendor/laravel/jetstream/**/*.blade.php", "./storage/framework/views/*.php", "./resources/views/**/*.blade.php", "./resources/js/**/*.vue"],

  theme: {
    extend: {
      fontFamily: {
        // sans: ["Figtree", ...defaultTheme.fontFamily.sans],
        sans: ["Inter var", defaultTheme.fontFamily.sans],
      },
      colors: {
        "light-blue": colors.lightBlue,
        green: colors.green,
      },
    },
  },

  plugins: [require("@tailwindcss/forms"), require("@tailwindcss/typography")],
}
