import {Injectable} from '@angular/core';
import {CanActivate, Router, ActivatedRouteSnapshot, RouterStateSnapshot} from '@angular/router';
import {Observable} from 'rxjs/Observable';
import {tap} from 'rxjs/operators';
import {ApiService} from './ApiService';

@Injectable()
export class AuthGuard implements CanActivate {
    constructor(private apiService: ApiService, private router: Router) {}

    canActivate(route: ActivatedRouteSnapshot, state: RouterStateSnapshot): Observable<boolean> {
        return this.apiService.isLoggedIn()
            .pipe(
                tap(isLoggedIn => {
                    if (!isLoggedIn) {
                        this.apiService.setRedirectUrl(state.url);
                        this.router.navigate(['/login']);
                    }
                })
            );
    }
}
