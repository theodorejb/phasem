import {HttpClientModule} from '@angular/common/http';
import {NgModule} from '@angular/core';
import {FormsModule} from '@angular/forms';
import {BrowserModule} from '@angular/platform-browser';
import {RouterModule} from '@angular/router';
import {LaddaModule} from 'angular2-ladda';
import {AppComponent} from './components/app/app';
import {LoadContentsComponent} from "./components/load-contents/load-contents";
import {AutofocusDirective} from "./directives/autofocus";
import {appComponents, appRoutes} from './routing';

@NgModule({
    imports: [
        BrowserModule,
        FormsModule,
        HttpClientModule,
        LaddaModule.forRoot({
            style: 'expand-right',
        }),
        RouterModule.forRoot(appRoutes),
    ],
    declarations: [
        AppComponent,
        ...appComponents,
        AutofocusDirective,
        LoadContentsComponent,
    ],
    bootstrap: [AppComponent],
})
export class AppModule {}
