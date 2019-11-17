import {Component, OnInit} from '@angular/core';
import {Title} from '@angular/platform-browser';
import {
    ActivatedRoute,
    Event,
    NavigationCancel,
    NavigationEnd,
    NavigationError,
    NavigationStart,
    Router,
} from '@angular/router';
import {ApiService} from '../../services/ApiService';

@Component({
    selector: 'app-root',
    templateUrl: 'app.html',
})
export class AppComponent implements OnInit {
    public loading = false;

    constructor(
        public api: ApiService,
        private router: Router,
        private activated: ActivatedRoute,
        private titleService: Title,
    ) {}

    ngOnInit() {
        this.router.events.subscribe((event: Event) => {
            switch (true) {
                case event instanceof NavigationStart: {
                    this.loading = true;
                    break;
                }

                case event instanceof NavigationEnd: {
                    let route = this.activated;

                    while (route.firstChild) {
                        route = route.firstChild;
                    }

                    const data = route.snapshot.data;
                    let title = 'Phasem';

                    if (data.title) {
                        title = `${data.title} | ${title}`;
                    }

                    this.titleService.setTitle(title);
                    // intentional fallthrough
                }

                case event instanceof NavigationCancel:
                case event instanceof NavigationError: {
                    this.loading = false;
                    break;
                }
            }
        });

        this.api.isLoggedIn().subscribe(() => {/*do nothing*/}); // force login check
    }
}
