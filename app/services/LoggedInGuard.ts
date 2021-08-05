import {Injectable} from '@angular/core';
import {ActivatedRouteSnapshot, CanActivate, Router, RouterStateSnapshot} from '@angular/router';
import {Observable, tap} from 'rxjs';
import {ApiService} from './ApiService';

@Injectable({
    providedIn: 'root',
})
export class LoggedInGuard implements CanActivate {
    constructor(private apiService: ApiService, private router: Router) {}

    canActivate(route: ActivatedRouteSnapshot, state: RouterStateSnapshot): Observable<boolean> {
        return this.apiService.isLoggedIn()
            .pipe(
                tap(isLoggedIn => {
                    if (!isLoggedIn) {
                        this.apiService.setRedirectUrl(state.url);
                        this.router.navigate(['/login']);
                    }
                }),
            );
    }
}
