import {Injectable} from '@angular/core';
import {ActivatedRouteSnapshot, CanActivate, RouterStateSnapshot} from '@angular/router';
import {Observable, map} from 'rxjs';
import {ApiService} from './ApiService';

@Injectable({
    providedIn: 'root',
})
export class NoAuthGuard implements CanActivate {
    constructor(private apiService: ApiService) {}

    canActivate(route: ActivatedRouteSnapshot, state: RouterStateSnapshot): Observable<boolean> {
        return this.apiService.isLoggedIn()
            .pipe(
                map(isLoggedIn => {
                    if (isLoggedIn) {
                        this.apiService.defaultRedirect();
                    }

                    return !isLoggedIn;
                }),
            );
    }
}
