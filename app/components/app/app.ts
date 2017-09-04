import {Component, OnInit} from '@angular/core';
import {ActivatedRoute, Router, NavigationEnd} from '@angular/router';
import {Title} from '@angular/platform-browser';
import {ApiService} from '../../services/ApiService';

@Component({
    selector: 'my-app',
    templateUrl: 'app.html',
})
export class AppComponent implements OnInit {
    constructor(
        private api: ApiService,
        private router: Router,
        private activated: ActivatedRoute,
        private titleService: Title
    ) {}

    ngOnInit() {
        this.router.events
            .filter(event => event instanceof NavigationEnd)
            .map(_ => {
                let route = this.activated;

                while(route.firstChild) {
                    route = route.firstChild;
                }

                return route.snapshot.data;
            })
            .subscribe(data => {
                let title = 'Phasem';

                if (data.title) {
                    title = `${data.title} | ${title}`;
                }

                this.titleService.setTitle(title);
            });
    }

    currentUser() {
        return this.api.getCurrentUser();
    }
}
