import {Component} from '@angular/core';

@Component({
    template: `
        <div>
            <h1>404</h1>
            <p>Sorry, the specified page could not be found.</p>
            <p>Please double-check the URL for mistakes and try again.</p>
            <p><a [routerLink]="['/']">Return home</a></p>
        </div>`,
})
export class PageNotFoundComponent {
}
