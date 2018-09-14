import {Component, OnInit} from '@angular/core';
import {Title} from '@angular/platform-browser';
import {ActivatedRoute, NavigationEnd, Router} from '@angular/router';
import {filter, map} from 'rxjs/operators';
import {ApiService} from '../../services/ApiService';

@Component({
    selector: 'my-app',
    templateUrl: 'app.html',
})
export class AppComponent implements OnInit {
    constructor(
        public api: ApiService,
        private router: Router,
        private activated: ActivatedRoute,
        private titleService: Title,
    ) {}

    ngOnInit() {
        this.router.events
            .pipe(
                filter(event => event instanceof NavigationEnd),
                map(_ => {
                    let route = this.activated;

                    while (route.firstChild) {
                        route = route.firstChild;
                    }

                    return route.snapshot.data;
                }),
            )
            .subscribe(data => {
                let title = 'Phasem';

                if (data.title) {
                    title = `${data.title} | ${title}`;
                }

                this.titleService.setTitle(title);
            });

        this.api.isLoggedIn().subscribe(() => {/*do nothing*/}); // force login check
    }
}
