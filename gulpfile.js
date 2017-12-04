var elixir = require('laravel-elixir')

require('laravel-elixir-vue-2')

elixir(function(mix) {
  mix.webpack('app.js'); // resources/assets/js/app.js
})

elixir(function(mix) {
    mix.sass('app.scss');
});
