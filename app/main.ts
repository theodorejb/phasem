import {enableProdMode} from '@angular/core';
import {platformBrowser} from '@angular/platform-browser';
import {AppModuleNgFactory} from './app.module.ngfactory';

if (document.location.host !== 'localhost') {
    enableProdMode();
}

platformBrowser().bootstrapModuleFactory(AppModuleNgFactory);